<?php

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Block;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatAllocation;
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
            ->has('student.allocation_history', 0)
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('staff can view a student current room and complete allocation history', function () {
    $viewer = studentProfileUserWithRole('hall_office_staff');
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create();
    $oldSeat = Seat::factory()->for($room)->create(['label' => 'A']);
    $currentSeat = Seat::factory()->for($room)->create(['label' => 'B']);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Resident,
    ]);
    $historical = SeatAllocation::factory()->for($student)->for($oldSeat)->create([
        'allocated_at' => '2025-08-01',
        'active' => null,
        'vacated_at' => '2026-01-10',
        'vacancy_reason' => 'Room change',
    ]);
    $active = SeatAllocation::factory()->for($student)->for($currentSeat)->create([
        'allocated_at' => '2026-01-15',
    ]);

    $this->actingAs($viewer)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('students/show', false)
            ->where('student.current_room.id', $room->id)
            ->where('student.current_room.room_no', $room->room_no)
            ->where('student.block.id', $block->id)
            ->where('student.block.name', $block->name)
            ->has('student.allocation_history', 2)
            ->where('student.allocation_history.0.id', $active->id)
            ->where('student.allocation_history.0.room_no', $room->room_no)
            ->where('student.allocation_history.0.block', $block->name)
            ->where('student.allocation_history.0.started_at', '2026-01-15')
            ->where('student.allocation_history.0.vacated_at', null)
            ->where('student.allocation_history.0.vacancy_reason', null)
            ->where('student.allocation_history.1.id', $historical->id)
            ->where('student.allocation_history.1.started_at', '2025-08-01')
            ->where('student.allocation_history.1.vacated_at', '2026-01-10')
            ->where('student.allocation_history.1.vacancy_reason', 'Room change')
            ->where('activeAllocation.id', $active->id)
        );
});

test('students can view their own current room and allocation history', function () {
    $viewer = studentProfileUserWithRole('student');
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create();
    $seat = Seat::factory()->for($room)->create();
    $active = SeatAllocation::factory()->for($viewer->studentProfile)->for($seat)->create([
        'allocated_at' => '2026-02-01',
    ]);

    $this->actingAs($viewer)
        ->get(route('students.show', $viewer->studentProfile))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('student.current_room.id', $room->id)
            ->where('student.block.id', $block->id)
            ->has('student.allocation_history', 1)
            ->where('student.allocation_history.0.id', $active->id)
        );
});

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
