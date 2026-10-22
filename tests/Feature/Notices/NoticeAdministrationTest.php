<?php

use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('staff roles see all draft and published notices in the administrative list', function (string $role) {
    $user = noticeAdministrationUser($role);
    $draft = Notice::factory()->create(['title' => 'Draft notice']);
    $published = Notice::factory()->published()->create(['title' => 'Published notice']);

    $this->actingAs($user)
        ->get(route('admin.notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/index', false)
            ->has('notices.data', 2)
            ->where('can_create', true)
            ->where('notices.data', fn ($notices): bool => collect($notices)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$draft->id, $published->id])->sort()->values()->all())
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('a club admin sees only notices they created', function () {
    $clubAdmin = noticeAdministrationUser('club_admin');
    $otherClubAdmin = noticeAdministrationUser('club_admin');
    $ownDraft = Notice::factory()->for($clubAdmin, 'creator')->create(['title' => 'Own draft']);
    $ownPublished = Notice::factory()->for($clubAdmin, 'creator')->published()->create(['title' => 'Own published']);
    Notice::factory()->for($otherClubAdmin, 'creator')->create(['title' => 'Another draft']);
    Notice::factory()->for($otherClubAdmin, 'creator')->published()->create(['title' => 'Another published']);

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/index', false)
            ->has('notices.data', 2)
            ->where('notices.data', fn ($notices): bool => collect($notices)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$ownDraft->id, $ownPublished->id])->sort()->values()->all())
        );
});

test('a club admin without a student profile can open the administrative list', function () {
    $clubAdmin = noticeAdministrationUser('club_admin');

    expect($clubAdmin->studentProfile)->toBeNull();

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.index'))
        ->assertOk();
});

test('roles without notice authority cannot open the administrative list', function (string $role) {
    $this->actingAs(noticeAdministrationUser($role))
        ->get(route('admin.notices.index'))
        ->assertForbidden();
})->with(['house_tutor', 'student']);

test('guests are redirected to sign in before opening the administrative list', function () {
    $this->get(route('admin.notices.index'))
        ->assertRedirect(route('login'));
});

test('an inactive club admin is signed out before opening the administrative list', function () {
    $clubAdmin = noticeAdministrationUser('club_admin', active: false);

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.index'))
        ->assertRedirect(route('login'));
});

test('the create page exposes only categories the current author may use', function () {
    $staff = noticeAdministrationUser('hall_office_staff');
    $clubAdmin = noticeAdministrationUser('club_admin');

    $this->actingAs($staff)
        ->get(route('admin.notices.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/create', false)
            ->where('categories', ['provost_office', 'hall_office', 'club'])
            ->where('statuses', ['draft', 'published'])
        );

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/create', false)
            ->where('categories', ['club'])
            ->where('statuses', ['draft', 'published'])
        );
});

test('roles without notice authority cannot open the create page', function (string $role) {
    $this->actingAs(noticeAdministrationUser($role))
        ->get(route('admin.notices.create'))
        ->assertForbidden();
})->with(['house_tutor', 'student']);

function noticeAdministrationUser(string $role, bool $active = true): User
{
    $user = User::factory()->create(['is_active' => $active]);
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}
