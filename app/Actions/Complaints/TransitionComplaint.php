<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionComplaint
{
    /**
     * @var array<string, array<int, ComplaintStatus>>
     */
    private const ALLOWED_TRANSITIONS = [
        ComplaintStatus::Submitted->value => [
            ComplaintStatus::UnderReview,
            ComplaintStatus::Rejected,
        ],
        ComplaintStatus::UnderReview->value => [
            ComplaintStatus::InProgress,
            ComplaintStatus::Rejected,
        ],
        ComplaintStatus::InProgress->value => [
            ComplaintStatus::Resolved,
        ],
        ComplaintStatus::Resolved->value => [
            ComplaintStatus::Closed,
            ComplaintStatus::InProgress,
        ],
    ];

    public function handle(
        Complaint $complaint,
        ComplaintStatus $toStatus,
        User $actor,
        ?string $note = null,
    ): Complaint {
        return DB::transaction(function () use ($complaint, $toStatus, $actor, $note): Complaint {
            $lockedComplaint = Complaint::query()
                ->lockForUpdate()
                ->findOrFail($complaint->id);

            $fromStatus = $lockedComplaint->status;

            if (! in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus->value] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => __('The complaint cannot transition from :from to :to.', [
                        'from' => $fromStatus->value,
                        'to' => $toStatus->value,
                    ]),
                ]);
            }

            $lockedComplaint->update(['status' => $toStatus]);

            $lockedComplaint->statusLogs()->create([
                'actor_id' => $actor->id,
                'from_status' => $fromStatus,
                'status' => $toStatus,
                'note' => $note,
            ]);

            return $lockedComplaint;
        });
    }
}
