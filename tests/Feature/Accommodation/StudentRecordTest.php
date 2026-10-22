<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('staff can view any student profile with personal details', function (string $role) {
    $viewer = studentProfileUserWithRole($role);
    $student = StudentProfile::factory()->create([
        'registration_number' => '2023000123',
        'department' => 'English',
        'session' => '2023-24',
        'phone' => '01712345678',
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $student->user->update([
        'name' => 'Profile Student',
        'email' => 'profile@student.du.test',
    ]);

    $this->actingAs($viewer)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('students/show', false)
            ->where('student.id', $student->id)
            ->where('student.name', 'Profile Student')
            ->where('student.email', 'profile@student.du.test')
            ->where('student.registration_number', '2023000123')
            ->where('student.department', 'English')
            ->where('student.session', '2023-24')
            ->where('student.phone', '01712345678')
            ->where('student.registration_status', RegistrationStatus::Approved->value)
            ->where('student.residency_status', 'attached')
            ->where('student.current_room', null)
            ->where('student.allocation_history_available', false)
            ->has('student.allocation_history', 0)
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('students and club admins can view their own profile', function (string $role) {
    $viewer = studentProfileUserWithRole($role);
    $profile = $viewer->studentProfile;

    $this->actingAs($viewer)
        ->get(route('students.show', $profile))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('students/show', false)
            ->where('student.id', $profile->id)
            ->where('student.name', $viewer->name)
        );
})->with(['student', 'club_admin']);

test('students and club admins cannot view another student profile', function (string $role) {
    $viewer = studentProfileUserWithRole($role);
    $other = StudentProfile::factory()->create();

    $this->actingAs($viewer)
        ->get(route('students.show', $other))
        ->assertForbidden();
})->with(['student', 'club_admin']);

test('house tutors cannot view student profiles before block assignments exist', function () {
    $other = StudentProfile::factory()->create();

    $this->actingAs(studentProfileUserWithRole('house_tutor'))
        ->get(route('students.show', $other))
        ->assertForbidden();
});

function studentProfileUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    if (in_array($role, ['student', 'club_admin'], true)) {
        StudentProfile::factory()->for($user)->create([
            'registration_status' => RegistrationStatus::Approved,
        ]);
    }

    return $user;
}
