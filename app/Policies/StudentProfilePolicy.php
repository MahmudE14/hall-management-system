<?php

namespace App\Policies;

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
}
