<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole('super_admin', 'provost', 'hall_office_staff')) {
            return true;
        }

        if ($user->hasAnyRole('student', 'club_admin')) {
            return $user->studentProfile !== null;
        }

        $tutorProfile = $user->tutorProfile;

        return $tutorProfile?->is_active === true
            && $tutorProfile->blocks()->exists();
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return Complaint::query()
            ->visibleTo($user)
            ->whereKey($complaint->getKey())
            ->exists();
    }
}
