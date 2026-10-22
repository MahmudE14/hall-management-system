<?php

namespace App\Actions\Accommodation;

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocateSeat
{
    public function handle(StudentProfile $student, Seat $seat): SeatAllocation
    {
        return DB::transaction(function () use ($student, $seat): SeatAllocation {
            $lockedStudent = StudentProfile::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            $lockedSeat = Seat::query()
                ->lockForUpdate()
                ->findOrFail($seat->id);

            if ($lockedStudent->registration_status !== RegistrationStatus::Approved) {
                throw ValidationException::withMessages([
                    'student_profile_id' => __('Only approved students can be allocated a seat.'),
                ]);
            }

            if ($lockedStudent->seatAllocations()->where('active', true)->exists()) {
                throw ValidationException::withMessages([
                    'student_profile_id' => __('This student already has an active seat allocation.'),
                ]);
            }

            if ($lockedSeat->seatAllocations()->where('active', true)->exists()) {
                throw ValidationException::withMessages([
                    'seat_id' => __('This seat is already occupied.'),
                ]);
            }

            $allocation = $lockedStudent->seatAllocations()->create([
                'seat_id' => $lockedSeat->id,
                'allocated_at' => now()->toDateString(),
                'active' => true,
            ]);

            $lockedStudent->update(['residency_status' => ResidencyStatus::Resident]);

            return $allocation;
        });
    }
}
