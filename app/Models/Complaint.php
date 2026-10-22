<?php

namespace App\Models;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $tracking_id
 * @property int $student_profile_id
 * @property int|null $room_id
 * @property string|null $location
 * @property ComplaintCategory $category
 * @property string $title
 * @property string $description
 * @property string|null $image_path
 * @property ComplaintStatus $status
 * @property int|null $assignee_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_note
 * @property string|null $rejection_reason
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tracking_id',
    'room_id',
    'location',
    'category',
    'title',
    'description',
    'image_path',
    'status',
    'assignee_id',
    'assigned_at',
    'resolved_at',
    'resolution_note',
    'rejection_reason',
])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<ComplaintStatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<ComplaintComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(ComplaintComment::class)->orderBy('created_at')->orderBy('id');
    }

    public function daysOpen(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, [ComplaintStatus::Resolved, ComplaintStatus::Closed, ComplaintStatus::Rejected], true)) {
            return false;
        }

        return $this->created_at->lt(now()->subDays(7));
    }

    /**
     * @param  Builder<Complaint>  $query
     * @return Builder<Complaint>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole('super_admin', 'provost', 'hall_office_staff')) {
            return $query;
        }

        if ($user->hasAnyRole('student', 'club_admin')) {
            return $query->where('student_profile_id', $user->studentProfile->id ?? 0);
        }

        $tutorProfile = $user->tutorProfile;

        if ($user->hasRole('house_tutor') && $tutorProfile?->is_active === true) {
            return $query->where('assignee_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ComplaintCategory::class,
            'status' => ComplaintStatus::class,
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
