<?php

namespace App\Policies;

use App\Models\SeatAllocation;
use App\Models\User;

class SeatAllocationPolicy
{
    /** @var array<int, string> */
    private const array MANAGE_ROLES = ['super_admin', 'provost', 'hall_office_staff'];

    public function create(User $user): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }

    public function update(User $user, SeatAllocation $seatAllocation): bool
    {
        return $user->hasAnyRole(...self::MANAGE_ROLES);
    }
}
