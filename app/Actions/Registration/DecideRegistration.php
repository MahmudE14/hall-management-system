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
        DB::transaction(function () use ($profile, $actor, $status, $reason): void {
            $lockedProfile = StudentProfile::query()
                ->lockForUpdate()
                ->findOrFail($profile->id);

            abort_unless(
                $lockedProfile->registration_status === RegistrationStatus::Pending,
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
