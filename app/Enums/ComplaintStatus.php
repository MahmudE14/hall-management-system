<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';
}
