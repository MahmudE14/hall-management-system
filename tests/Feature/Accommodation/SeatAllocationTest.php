<?php

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
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

test('authorized staff can allocate an available seat to an approved student', function (string $role) {
    $actor = seatAllocationUserWithRole($role);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $seat = Seat::factory()->create();

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $seat->id])
        ->assertRedirect(route('students.show', $student));

    $allocation = SeatAllocation::query()
        ->where('student_profile_id', $student->id)
        ->where('seat_id', $seat->id)
        ->first();

    expect($allocation)->not->toBeNull();
    expect($allocation->active)->toBeTrue();
    expect($allocation->allocated_at->toDateString())->toBe(now()->toDateString());
    expect($student->refresh()->residency_status)->toBe(ResidencyStatus::Resident);
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('student and room pages expose allocation controls and current occupancy', function () {
    $actor = seatAllocationUserWithRole('hall_office_staff');
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Attached,
    ]);
    $room = Room::factory()->create(['capacity' => 2]);
    $seatA = Seat::factory()->for($room)->create(['label' => 'A']);
    $seatB = Seat::factory()->for($room)->create(['label' => 'B']);

    $this->actingAs($actor)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('students/show', false)
            ->where('canAllocate', true)
            ->where('activeAllocation', null)
            ->has('availableSeats', 2)
            ->where('availableSeats.0.id', $seatA->id)
            ->where('availableSeats.1.id', $seatB->id)
        );

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $seatA->id])
        ->assertRedirect(route('students.show', $student));

    $this->actingAs($actor)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('student.residency_status', ResidencyStatus::Resident->value)
            ->where('activeAllocation.seat.id', $seatA->id)
            ->where('activeAllocation.room.id', $room->id)
            ->has('availableSeats', 0)
        );

    $this->actingAs($actor)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rooms.0.occupied_seats', 1)
            ->where('rooms.0.free_seats', 1)
        );
});

test('a student with an active allocation cannot receive another seat', function () {
    $actor = seatAllocationUserWithRole('hall_office_staff');
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Resident,
    ]);
    $existing = SeatAllocation::factory()->for($student)->create();
    $availableSeat = Seat::factory()->create();

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $availableSeat->id])
        ->assertSessionHasErrors('student_profile_id');

    expect(SeatAllocation::query()->where('seat_id', $availableSeat->id)->exists())->toBeFalse();
    expect($student->refresh()->residency_status)->toBe(ResidencyStatus::Resident);
    expect($existing->refresh()->active)->toBeTrue();
});

test('an occupied seat cannot be allocated to another student', function () {
    $actor = seatAllocationUserWithRole('hall_office_staff');
    $occupant = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $seat = Seat::factory()->create();
    $existing = SeatAllocation::factory()->for($occupant)->for($seat)->create();

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $seat->id])
        ->assertSessionHasErrors('seat_id');

    expect(SeatAllocation::query()->where('student_profile_id', $student->id)->exists())->toBeFalse();
    expect($student->refresh()->residency_status)->toBe(ResidencyStatus::Attached);
    expect($existing->refresh()->active)->toBeTrue();
});

test('a student without an approved registration cannot receive a seat', function (RegistrationStatus $status) {
    $actor = seatAllocationUserWithRole('hall_office_staff');
    $student = StudentProfile::factory()->create(['registration_status' => $status]);
    $seat = Seat::factory()->create();

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $seat->id])
        ->assertSessionHasErrors('student_profile_id');

    expect(SeatAllocation::query()->where('student_profile_id', $student->id)->exists())->toBeFalse();
    expect($student->refresh()->residency_status)->toBe(ResidencyStatus::Attached);
})->with([
    'pending' => RegistrationStatus::Pending,
    'rejected' => RegistrationStatus::Rejected,
]);

test('unauthorized roles cannot allocate seats', function (string $role) {
    $actor = seatAllocationUserWithRole($role);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $seat = Seat::factory()->create();

    $this->actingAs($actor)
        ->post(route('allocations.store', $student), ['seat_id' => $seat->id])
        ->assertForbidden();

    expect(SeatAllocation::query()->where('student_profile_id', $student->id)->exists())->toBeFalse();
})->with(['student', 'club_admin', 'house_tutor']);

function seatAllocationUserWithRole(string $role): User
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
