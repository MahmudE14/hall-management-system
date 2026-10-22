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

test('authorized staff can vacate an active allocation and free the seat', function (string $role) {
    $actor = vacancyUserWithRole($role);
    $room = Room::factory()->create(['capacity' => 2]);
    $seat = Seat::factory()->for($room)->create(['label' => 'A']);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Resident,
    ]);
    $allocation = SeatAllocation::factory()->for($student)->for($seat)->create([
        'allocated_at' => '2026-01-15',
    ]);

    $this->actingAs($actor)
        ->patch(route('allocations.vacate', $allocation), [
            'vacated_at' => '2026-02-20',
            'vacancy_reason' => 'Completed studies',
        ])
        ->assertRedirect(route('students.show', $student))
        ->assertSessionHasNoErrors();

    $allocation->refresh();

    expect($allocation->active)->toBeNull()
        ->and($allocation->vacated_at->toDateString())->toBe('2026-02-20')
        ->and($allocation->vacancy_reason)->toBe('Completed studies')
        ->and($student->refresh()->residency_status)->toBe(ResidencyStatus::Attached)
        ->and($seat->seatAllocations()->where('active', true)->exists())->toBeFalse()
        ->and(SeatAllocation::query()->whereKey($allocation->id)->exists())->toBeTrue();

    $this->actingAs($actor)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rooms.0.occupied_seats', 0)
            ->where('rooms.0.free_seats', 2)
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('vacancy requires a date and reason', function () {
    $actor = vacancyUserWithRole('hall_office_staff');
    $allocation = SeatAllocation::factory()->create();

    $this->actingAs($actor)
        ->patch(route('allocations.vacate', $allocation), [])
        ->assertSessionHasErrors(['vacated_at', 'vacancy_reason']);

    expect($allocation->refresh()->active)->toBeTrue();
});

test('unauthorized roles cannot vacate an allocation', function (string $role) {
    $actor = vacancyUserWithRole($role);
    $allocation = SeatAllocation::factory()->create();

    $this->actingAs($actor)
        ->patch(route('allocations.vacate', $allocation), [
            'vacated_at' => '2026-02-20',
            'vacancy_reason' => 'Unauthorized attempt',
        ])
        ->assertForbidden();

    expect($allocation->refresh()->active)->toBeTrue();
})->with(['student', 'club_admin', 'house_tutor']);

test('an already closed allocation cannot be vacated again', function () {
    $actor = vacancyUserWithRole('hall_office_staff');
    $allocation = SeatAllocation::factory()->create([
        'active' => null,
        'vacated_at' => '2026-02-20',
        'vacancy_reason' => 'Completed studies',
    ]);

    $this->actingAs($actor)
        ->patch(route('allocations.vacate', $allocation), [
            'vacated_at' => '2026-03-01',
            'vacancy_reason' => 'Repeated vacancy',
        ])
        ->assertSessionHasErrors('allocation');

    expect($allocation->refresh())
        ->vacated_at->toDateString()->toBe('2026-02-20')
        ->vacancy_reason->toBe('Completed studies');
});

test('a vacated seat can be reallocated without deleting the retained history', function () {
    $actor = vacancyUserWithRole('hall_office_staff');
    $seat = Seat::factory()->create();
    $previousStudent = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Resident,
    ]);
    $previous = SeatAllocation::factory()->for($previousStudent)->for($seat)->create();
    $nextStudent = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs($actor)
        ->patch(route('allocations.vacate', $previous), [
            'vacated_at' => '2026-02-20',
            'vacancy_reason' => 'Room change',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($actor)
        ->post(route('allocations.store', $nextStudent), ['seat_id' => $seat->id])
        ->assertSessionHasNoErrors();

    $activeAllocation = $seat->seatAllocations()->where('active', true)->sole();

    expect($activeAllocation->student_profile_id)->toBe($nextStudent->id)
        ->and($activeAllocation->id)->not->toBe($previous->id)
        ->and(SeatAllocation::query()->whereKey($previous->id)->exists())->toBeTrue()
        ->and($previous->refresh()->vacancy_reason)->toBe('Room change')
        ->and($previousStudent->refresh()->residency_status)->toBe(ResidencyStatus::Attached)
        ->and($nextStudent->refresh()->residency_status)->toBe(ResidencyStatus::Resident);
});

function vacancyUserWithRole(string $role): User
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
