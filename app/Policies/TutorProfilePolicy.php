<?php

namespace App\Policies;

use App\Models\TutorProfile;
use App\Models\User;

class TutorProfilePolicy
{
    /** @var array<int, string> */
    private const array MANAGE_ROLES = ['super_admin', 'provost'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }

    public function update(User $user, TutorProfile $tutorProfile): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }

    public function deactivate(User $user, TutorProfile $tutorProfile): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }
}
