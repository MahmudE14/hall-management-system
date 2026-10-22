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
        return $user->hasAnyRole(
            ...self::STAFF_VIEW_ROLES,
            ...self::SELF_VIEW_ROLES,
        );
    }

    public function view(User $user, StudentProfile $studentProfile): bool
    {
        if ($user->hasAnyRole(...self::STAFF_VIEW_ROLES)) {
            return true;
        }

        return $user->hasAnyRole(...self::SELF_VIEW_ROLES)
            && $studentProfile->user_id === $user->id;
    }
}
