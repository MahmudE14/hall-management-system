<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('super admins can view users with roles and registration status', function () {
    $superAdmin = managedUserWithRole('super_admin');
    $student = User::factory()->create(['name' => 'Searchable Student']);
    $student->roles()->attach(Role::factory()->create(['name' => 'student']));
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
