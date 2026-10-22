<?php

namespace App\Actions\Complaints;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignComplaint
{
    public function handle(Complaint $complaint, User $assignee): Complaint
    {
        return DB::transaction(function () use ($complaint, $assignee): Complaint {
            $lockedComplaint = Complaint::query()
                ->lockForUpdate()
                ->findOrFail($complaint->id);

            $lockedAssignee = User::query()
                ->lockForUpdate()
                ->findOrFail($assignee->id);

            $lockedAssignee->load('tutorProfile');

            if (! $this->isEligible($lockedAssignee)) {
                throw ValidationException::withMessages([
                    'assignee_id' => __('The selected user cannot be assigned complaints.'),
                ]);
            }

            $lockedComplaint->update([
                'assignee_id' => $lockedAssignee->id,
                'assigned_at' => now(),
            ]);

            return $lockedComplaint;
        });
    }

    private function isEligible(User $assignee): bool
    {
        if (! $assignee->is_active) {
            return false;
        }

        if ($assignee->hasRole('hall_office_staff')) {
            return true;
        }

        return $assignee->hasRole('house_tutor')
            && $assignee->tutorProfile?->is_active === true;
    }
}
