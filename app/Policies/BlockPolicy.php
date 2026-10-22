<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    /** @var array<int, string> */
    private const array VIEW_ROLES = ['super_admin', 'provost', 'hall_office_staff'];

    /** @var array<int, string> */
    private const array MANAGE_ROLES = ['super_admin', 'hall_office_staff'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(...self::VIEW_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }

    public function update(User $user, Block $block): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }
}
