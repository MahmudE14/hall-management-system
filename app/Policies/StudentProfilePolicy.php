<?php

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    /** @var array<int, string> */
    private const array STAFF_VIEW_ROLES = ['super_admin', 'provost', 'hall_office_staff'];

    /** @var array<int, string> */
    private const array SELF_VIEW_ROLES = ['student', 'club_admin'];

    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(
            ...self::STAFF_VIEW_ROLES,
            ...self::SELF_VIEW_ROLES,
        )) {
            return true;
        }

        $tutorProfile = $user->tutorProfile;

        return $tutorProfile?->is_active === true
            && $tutorProfile->blocks()->exists();
    }

    public function view(User $user, StudentProfile $studentProfile): bool
    {
        if ($user->hasAnyRole(...self::STAFF_VIEW_ROLES)) {
            return true;
        }

        if ($user->hasAnyRole(...self::SELF_VIEW_ROLES)
            && $studentProfile->user_id === $user->id) {
            return true;
        }

        $tutorProfile = $user->tutorProfile;

        if ($tutorProfile?->is_active !== true) {
            return false;
        }

        $blockId = $studentProfile->activeSeatAllocation?->seat?->room?->block_id;

        return $blockId !== null
            && $tutorProfile->blocks()->whereKey($blockId)->exists();
    }
}
