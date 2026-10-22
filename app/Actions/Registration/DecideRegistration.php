<?php

namespace App\Actions\Registration;

use App\Enums\RegistrationStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DecideRegistration
{
    public function handle(StudentProfile $profile, User $actor, RegistrationStatus $status, ?string $reason = null): void
    {
        $this->transition($profile, $actor, $status, $reason, [RegistrationStatus::Pending]);
    }

    public function reconsider(StudentProfile $profile, User $actor): void
    {
        $this->transition($profile, $actor, RegistrationStatus::Pending, null, [RegistrationStatus::Rejected]);
    }

    /**
     * @param  array<int, RegistrationStatus>  $allowedFrom
     */
    private function transition(
        StudentProfile $profile,
        User $actor,
        RegistrationStatus $status,
        ?string $reason,
        array $allowedFrom,
    ): void {
        DB::transaction(function () use ($profile, $actor, $status, $reason, $allowedFrom): void {
            $lockedProfile = StudentProfile::query()
                ->lockForUpdate()
                ->findOrFail($profile->id);

            abort_unless(
                in_array($lockedProfile->registration_status, $allowedFrom, true),
                422,
            );

            $lockedProfile->update(['registration_status' => $status]);

            $lockedProfile->registrationDecisions()->create([
                'actor_id' => $actor->id,
                'status' => $status,
                'reason' => $reason,
            ]);
        });
    }
}
