<?php

namespace App\Policies;

use App\Enums\NoticeCategory;
use App\Models\User;

class NoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAuthor($user);
    }

    public function create(User $user): bool
    {
        return $this->canAuthor($user);
    }

    public function createInCategory(User $user, NoticeCategory $category): bool
    {
        if (! $this->canAuthor($user)) {
            return false;
        }

        if ($user->hasAnyRole('super_admin', 'provost', 'hall_office_staff')) {
            return true;
        }

        return $user->hasRole('club_admin') && $category === NoticeCategory::Club;
    }

    private function canAuthor(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole('super_admin', 'provost', 'hall_office_staff', 'club_admin');
    }
}
