<?php

use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('seat allocations table contains the required columns', function () {
    expect(Schema::hasColumns('seat_allocations', [
        'id',
        'student_profile_id',
        'seat_id',
        'allocated_at',
        'vacated_at',
        'vacancy_reason',
        'active',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('seat allocation factories persist records', function () {
    $allocation = SeatAllocation::factory()->create();

    expect($allocation->refresh()->student_profile_id)->toBe($allocation->student_profile_id);
    expect($allocation->seat_id)->toBe($allocation->seat_id);
    expect($allocation->allocated_at->toDateString())->toBe(now()->toDateString());
    expect($allocation->vacated_at)->toBeNull();
    expect($allocation->vacancy_reason)->toBeNull();
    expect($allocation->active)->toBeTrue();
});

test('a student profile has many seat allocations', function () {
    $student = StudentProfile::factory()->create();
    $active = SeatAllocation::factory()->for($student)->create();
    $completed = SeatAllocation::factory()->for($student)->create(['active' => null]);

    expect($student->seatAllocations()->pluck('id')->all())->toEqualCanonicalizing([$active->id, $completed->id]);
    expect($student->seatAllocations)->toHaveCount(2);
});

test('a seat has many seat allocations', function () {
    $seat = Seat::factory()->create();
    $active = SeatAllocation::factory()->for($seat)->create();
    $completed = SeatAllocation::factory()->for($seat)->create(['active' => null]);

    expect($seat->seatAllocations()->pluck('id')->all())->toEqualCanonicalizing([$active->id, $completed->id]);
    expect($seat->seatAllocations)->toHaveCount(2);
});

test('a seat allocation belongs to a student profile and a seat', function () {
    $allocation = SeatAllocation::factory()->create();

    expect($allocation->studentProfile)->toBeInstanceOf(StudentProfile::class);
    expect($allocation->studentProfile->is(StudentProfile::find($allocation->student_profile_id)))->toBeTrue();
    expect($allocation->seat)->toBeInstanceOf(Seat::class);
    expect($allocation->seat->is(Seat::find($allocation->seat_id)))->toBeTrue();
});

test('a student cannot have more than one active seat allocation', function () {
    $student = StudentProfile::factory()->create();
    $firstAllocation = SeatAllocation::factory()->for($student)->create();

    expect(fn () => SeatAllocation::factory()->for($student)->create())
        ->toThrow(QueryException::class);

    expect($firstAllocation->refresh()->active)->toBeTrue();
});

test('a seat cannot have more than one active allocation', function () {
    $seat = Seat::factory()->create();
    $firstAllocation = SeatAllocation::factory()->for($seat)->create();

    expect(fn () => SeatAllocation::factory()->for($seat)->create())
        ->toThrow(QueryException::class);

    expect($firstAllocation->refresh()->active)->toBeTrue();
});

test('completed allocations may retain the same student and seat', function () {
    $student = StudentProfile::factory()->create();
    $seat = Seat::factory()->create();

    SeatAllocation::factory()->for($student)->for($seat)->create([
        'active' => null,
        'vacated_at' => now()->subYear(),
        'vacancy_reason' => 'Session ended.',
    ]);
    SeatAllocation::factory()->for($student)->for($seat)->create([
        'active' => null,
        'vacated_at' => now()->subMonths(6),
        'vacancy_reason' => 'Transferred.',
    ]);

    expect(SeatAllocation::query()
        ->where('student_profile_id', $student->id)
        ->where('seat_id', $seat->id)
        ->whereNull('active')
        ->count())->toBe(2);
});
