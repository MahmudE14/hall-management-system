<?php

namespace App\Actions\Accommodation;

use App\Enums\ResidencyStatus;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacateSeat
{
    public function handle(SeatAllocation $allocation, string $vacatedAt, string $reason): SeatAllocation
    {
        return DB::transaction(function () use ($allocation, $vacatedAt, $reason): SeatAllocation {
            $lockedAllocation = SeatAllocation::query()
                ->lockForUpdate()
                ->findOrFail($allocation->id);

            if ($lockedAllocation->active !== true) {
                throw ValidationException::withMessages([
                    'allocation' => __('This allocation is already closed.'),
                ]);
            }

            $student = StudentProfile::query()
                ->lockForUpdate()
                ->findOrFail($lockedAllocation->student_profile_id);

            $lockedAllocation->update([
                'vacated_at' => $vacatedAt,
                'vacancy_reason' => $reason,
                'active' => null,
            ]);

            $student->update(['residency_status' => ResidencyStatus::Attached]);

            return $lockedAllocation;
        });
    }
}
