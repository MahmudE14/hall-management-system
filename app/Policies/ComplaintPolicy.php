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

        return $user->hasRole('house_tutor')
            && $tutorProfile?->is_active === true;
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return Complaint::query()
            ->visibleTo($user)
            ->whereKey($complaint->getKey())
            ->exists();
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->hasAnyRole('hall_office_staff', 'provost', 'super_admin');
    }

    public function comment(User $user, Complaint $complaint): bool
    {
        if (! $this->view($user, $complaint)) {
            return false;
        }

        if ($user->hasAnyRole('hall_office_staff', 'provost', 'super_admin')) {
            return true;
        }

        if ($complaint->assignee_id === $user->id) {
            return true;
        }

        return $user->studentProfile !== null
            && $complaint->student_profile_id === $user->studentProfile->id;
    }
}
