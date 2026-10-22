<?php

use App\Enums\RegistrationStatus;
use App\Models\RegistrationDecision;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('super admins can view users with roles and registration status', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $student = User::factory()->create(['name' => 'Searchable Student']);
    $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
    StudentProfile::factory()->for($student)->create([
        'registration_number' => '2023000099',
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 2)
            ->where('users.data', fn ($users) => collect($users)->contains(
                fn ($user) => $user['name'] === 'Searchable Student'
                    && $user['roles'] === ['student']
                    && $user['student_profile']['registration_number'] === '2023000099'
                    && $user['student_profile']['registration_status'] === RegistrationStatus::Approved->value,
            ))
        );
});

test('other roles cannot list users', function (string $role) {
    $this->actingAs(managedUserWithRole($role))
        ->get(route('admin.users.index'))
        ->assertForbidden();
})->with(['provost', 'hall_office_staff', 'house_tutor', 'club_admin', 'student']);

test('super admins can search users by name, email, or registration number', function (string $search) {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');
    $target->update(['name' => 'Target Student', 'email' => 'target@student.du.test']);
    StudentProfile::factory()->for($target)->create(['registration_number' => '2023000077']);
    managedUserWithRole('provost');

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index', ['search' => $search]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'target@student.du.test')
        );
})->with([
    'name' => 'Target Student',
    'email' => 'target@student.du.test',
    'registration number' => '2023000077',
]);

test('super admins can filter users by role and registration status', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $approved = managedUserWithRole('student');
    StudentProfile::factory()->for($approved)->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $pending = managedUserWithRole('student');
    StudentProfile::factory()->for($pending)->create([
        'registration_status' => RegistrationStatus::Pending,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index', ['registration_status' => 'approved']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $approved->id)
        );

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index', ['role' => 'super_admin']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $superAdmin->id)
        );
});

test('user listing is paginated', function () {
    $superAdmin = managedUserWithRole('super_admin');
    User::factory()->count(24)->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 20)
            ->where('users.total', 25)
            ->where('users.per_page', 20)
        );
});

function managedUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}

beforeEach(function () {
    collect(Role::DOCUMENTED)->each(
        fn (string $role) => Role::query()->firstOrCreate(['name' => $role]),
    );
});

test('super admins can view a user role management page', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');

    $this->actingAs($superAdmin)
        ->get(route('admin.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/users/edit')
            ->where('user.id', $target->id)
            ->where('user.roles', ['student'])
            ->has('roles', count(Role::DOCUMENTED))
        );
});

test('super admins can update multiple roles without changing identity', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');
    $target->update(['name' => 'Role Target', 'email' => 'role-target@student.du.test']);
    StudentProfile::factory()->for($target)->create();
    $targetId = $target->id;

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), [
            'roles' => ['club_admin', 'student'],
        ])
        ->assertRedirect(route('admin.users.edit', $target));

    $target->refresh();

    expect($target->id)->toBe($targetId)
        ->and($target->name)->toBe('Role Target')
        ->and($target->email)->toBe('role-target@student.du.test')
        ->and($target->roles()->pluck('name')->all())->toEqualCanonicalizing(['club_admin', 'student']);
});

test('role updates reject undocumented roles', function (string $role) {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), ['roles' => [$role]])
        ->assertSessionHasErrors('roles.0');

    expect($target->refresh()->roles()->pluck('name')->all())->toBe(['student']);
})->with(['not_a_role', 'ghost_role']);

test('role updates require at least one role', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), ['roles' => []])
        ->assertSessionHasErrors('roles');
});

test('student role assignment is rejected when the user has no student profile', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('provost');

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), ['roles' => ['student']])
        ->assertSessionHasErrors('roles');

    expect($target->refresh()->roles()->pluck('name')->all())->toBe(['provost'])
        ->and($target->studentProfile()->exists())->toBeFalse();
});

test('student role assignment succeeds when the user already has a student profile', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('provost');
    $profile = StudentProfile::factory()->for($target)->create([
        'registration_number' => '2023000088',
    ]);

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), ['roles' => ['provost', 'student']])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.edit', $target));

    expect($target->refresh()->roles()->pluck('name')->all())->toEqualCanonicalizing(['provost', 'student'])
        ->and($profile->refresh()->registration_number)->toBe('2023000088');
});

test('rejected student role assignments preserve existing roles without creating a profile', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');
    $target->roles()->attach(Role::query()->where('name', 'provost')->firstOrFail());

    $this->actingAs($superAdmin)
        ->put(route('admin.users.roles.update', $target), ['roles' => ['student', 'provost', 'club_admin']])
        ->assertSessionHasErrors('roles');

    expect($target->refresh()->roles()->pluck('name')->all())->toEqualCanonicalizing(['student', 'provost'])
        ->and($target->studentProfile()->exists())->toBeFalse();
});

test('other roles cannot manage user roles', function (string $role) {
    $target = managedUserWithRole('student');

    $this->actingAs(managedUserWithRole($role))
        ->get(route('admin.users.edit', $target))
        ->assertForbidden();

    $this->actingAs(managedUserWithRole($role))
        ->put(route('admin.users.roles.update', $target), ['roles' => ['student']])
        ->assertForbidden();
})->with(['provost', 'hall_office_staff', 'house_tutor', 'club_admin', 'student']);

test('super admins can activate and deactivate accounts without touching identity, roles, or decisions', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $target = managedUserWithRole('student');
    $profile = StudentProfile::factory()->for($target)->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $decision = RegistrationDecision::factory()->for($profile)->create([
        'actor_id' => $superAdmin->id,
        'status' => RegistrationStatus::Approved,
    ]);
    $targetName = $target->name;

    $this->actingAs($superAdmin)
        ->patch(route('admin.users.deactivate', $target))
        ->assertRedirect(route('admin.users.edit', $target));

    $target->refresh();

    expect($target->is_active)->toBeFalse()
        ->and($target->name)->toBe($targetName)
        ->and($target->roles()->pluck('name')->all())->toBe(['student'])
        ->and($profile->refresh()->registration_status)->toBe(RegistrationStatus::Approved)
        ->and($decision->fresh())->not->toBeNull();

    $this->actingAs($superAdmin)
        ->patch(route('admin.users.activate', $target))
        ->assertRedirect(route('admin.users.edit', $target));

    expect($target->refresh()->is_active)->toBeTrue()
        ->and($target->roles()->pluck('name')->all())->toBe(['student'])
        ->and($profile->refresh()->registration_status)->toBe(RegistrationStatus::Approved);
});

test('other roles cannot activate or deactivate accounts', function (string $role) {
    $target = managedUserWithRole('student');
    $actor = managedUserWithRole($role);

    $this->actingAs($actor)
        ->patch(route('admin.users.deactivate', $target))
        ->assertForbidden();

    $this->actingAs($actor)
        ->patch(route('admin.users.activate', $target))
        ->assertForbidden();

    expect($target->refresh()->is_active)->toBeTrue();
})->with(['provost', 'hall_office_staff', 'house_tutor', 'club_admin', 'student']);
