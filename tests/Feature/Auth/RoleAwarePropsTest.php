<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('shared auth props expose assigned roles', function (string $role) {
    $user = userWithRoles([$role]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.roles', [$role])
            ->where('auth.user.id', $user->id)
            ->where('auth.user.email', $user->email)
        );
})->with(['super_admin', 'provost', 'hall_office_staff', 'house_tutor', 'club_admin', 'student']);

test('shared auth props expose the student registration status', function () {
    $user = userWithRoles(['student']);
    StudentProfile::factory()->for($user)->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.student_profile.registration_status', RegistrationStatus::Approved->value)
        );
});

test('staff-only registration routes reject roles without documented access', function (string $role) {
    $this->actingAs(userWithRoles([$role]))
        ->get(route('registrations.index'))
        ->assertForbidden();
})->with(['student', 'club_admin', 'house_tutor']);

/**
 * @param  array<int, string>  $roles
 */
function userWithRoles(array $roles): User
{
    $user = User::factory()->create();
    $roleIds = collect($roles)
        ->map(fn (string $role) => Role::factory()->create(['name' => $role])->id)
        ->all();

    $user->roles()->attach($roleIds);

    return $user;
}
