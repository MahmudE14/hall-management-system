<?php

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('the administrative notice list exposes authoring state for every authoring role', function (string $role) {
    $user = noticeAuthoringPageUser($role);
    $draft = Notice::factory()->for($user, 'creator')->pinned()->create([
        'title' => 'Pinned draft',
        'attachment_path' => 'notices/generated.pdf',
    ]);
    $published = Notice::factory()->for($user, 'creator')->published()->create([
        'title' => 'Published notice',
    ]);

    $this->actingAs($user)
        ->get(route('admin.notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/index', false)
            ->where('can_create', true)
            ->has('notices.data', 2)
            ->where('notices.data', function ($notices) use ($draft, $published): bool {
                $rows = collect($notices)->keyBy('id');

                return $rows->keys()->sort()->values()->all() === collect([$draft->id, $published->id])->sort()->values()->all()
                    && $rows[$draft->id]['status'] === NoticeStatus::Draft->value
                    && $rows[$draft->id]['is_pinned'] === true
                    && $rows[$draft->id]['has_attachment'] === true
                    && $rows[$draft->id]['creator']['name'] === $draft->creator->name
                    && $rows[$draft->id]['published_at'] === null
                    && $rows[$published->id]['status'] === NoticeStatus::Published->value
                    && $rows[$published->id]['has_attachment'] === false
                    && $rows[$published->id]['published_at'] !== null;
            })
        );
})->with(['super_admin', 'provost', 'hall_office_staff', 'club_admin']);

test('a club admin sees only their own notices with authoring controls', function () {
    $clubAdmin = noticeAuthoringPageUser('club_admin');
    $otherClubAdmin = noticeAuthoringPageUser('club_admin');
    $own = Notice::factory()->for($clubAdmin, 'creator')->create();
    Notice::factory()->for($otherClubAdmin, 'creator')->published()->create();

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/index', false)
            ->where('can_create', true)
            ->has('notices.data', 1)
            ->where('notices.data.0.id', $own->id)
            ->where('notices.data.0.creator.id', $clubAdmin->id)
        );
});

test('the create page exposes the permitted categories and statuses', function () {
    $staff = noticeAuthoringPageUser('hall_office_staff');
    $clubAdmin = noticeAuthoringPageUser('club_admin');

    $this->actingAs($staff)
        ->get(route('admin.notices.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/create', false)
            ->where('categories', array_column(NoticeCategory::cases(), 'value'))
            ->where('statuses', array_column(NoticeStatus::cases(), 'value'))
        );

    $this->actingAs($clubAdmin)
        ->get(route('admin.notices.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/create', false)
            ->where('categories', [NoticeCategory::Club->value])
            ->where('statuses', array_column(NoticeStatus::cases(), 'value'))
        );
});

test('invalid notice submissions return field errors to the authoring page', function () {
    $user = noticeAuthoringPageUser('hall_office_staff');

    $this->actingAs($user)
        ->from(route('admin.notices.create'))
        ->followingRedirects()
        ->post(route('admin.notices.store'), [])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/notices/create', false)
            ->where('errors.title', fn (string $message): bool => $message !== '')
            ->where('errors.body', fn (string $message): bool => $message !== '')
            ->where('errors.category', fn (string $message): bool => $message !== '')
            ->where('errors.status', fn (string $message): bool => $message !== '')
        );
});

test('a successful notice creation redirects with success feedback', function () {
    $user = noticeAuthoringPageUser('provost');

    $this->actingAs($user)
        ->post(route('admin.notices.store'), [
            'title' => 'Convocation notice',
            'body' => 'The convocation will be held next week.',
            'category' => NoticeCategory::ProvostOffice->value,
            'status' => NoticeStatus::Published->value,
        ])
        ->assertRedirect(route('admin.notices.index'))
        ->assertInertiaFlash('toast');

    expect(session('inertia.flash_data.toast.type'))->toBe('success');
});

function noticeAuthoringPageUser(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}
