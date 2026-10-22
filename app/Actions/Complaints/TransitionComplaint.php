<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

    /**
     * @var array<int, string>
     */
    private const STAFF_TRANSITIONS = [
        'submitted:under_review',
        'submitted:rejected',
        'under_review:in_progress',
        'under_review:rejected',
        'in_progress:resolved',
    ];

    /**
     * @var array<int, string>
     */
    private const TUTOR_TRANSITIONS = [
        'submitted:under_review',
        'under_review:in_progress',
        'in_progress:resolved',
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

            $this->authorizeTransition($lockedComplaint, $fromStatus, $toStatus, $actor);

            $note = $this->validateTransitionNote($toStatus, $note);

            $attributes = ['status' => $toStatus];

            if ($toStatus === ComplaintStatus::Rejected) {
                $attributes['rejection_reason'] = $note;
            }

            if ($toStatus === ComplaintStatus::Resolved) {
                $attributes['resolution_note'] = $note;
            }

            $lockedComplaint->update($attributes);

            $lockedComplaint->statusLogs()->create([
                'actor_id' => $actor->id,
                'from_status' => $fromStatus,
                'status' => $toStatus,
                'note' => $note,
            ]);

            return $lockedComplaint;
        });
    }

    private function authorizeTransition(
        Complaint $complaint,
        ComplaintStatus $fromStatus,
        ComplaintStatus $toStatus,
        User $actor,
    ): void {
        $edge = $fromStatus->value.':'.$toStatus->value;

        if ($actor->hasAnyRole('hall_office_staff', 'provost', 'super_admin')) {
            if (in_array($edge, self::STAFF_TRANSITIONS, true)) {
                return;
            }

            if ($edge === 'resolved:closed' && $actor->hasAnyRole('hall_office_staff', 'provost')) {
                return;
            }

            throw new AuthorizationException(__('You are not allowed to perform this complaint transition.'));
        }

        if ($this->isAssignedActiveTutor($complaint, $actor) && in_array($edge, self::TUTOR_TRANSITIONS, true)) {
            return;
        }

        throw new AuthorizationException(__('You are not allowed to perform this complaint transition.'));
    }

    private function isAssignedActiveTutor(Complaint $complaint, User $actor): bool
    {
        return $actor->hasRole('house_tutor')
            && $actor->tutorProfile?->is_active === true
            && $complaint->assignee_id === $actor->id;
    }

    private function validateTransitionNote(ComplaintStatus $toStatus, ?string $note): ?string
    {
        $normalizedNote = $note === null ? null : trim($note);

        if (
            in_array($toStatus, [ComplaintStatus::Rejected, ComplaintStatus::Resolved], true)
            && ($normalizedNote === null || $normalizedNote === '')
        ) {
            throw ValidationException::withMessages([
                'note' => $toStatus === ComplaintStatus::Rejected
                    ? __('A rejection reason is required.')
                    : __('A resolution note is required.'),
            ]);
        }

        return $normalizedNote;
    }
}
