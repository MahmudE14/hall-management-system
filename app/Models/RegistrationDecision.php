<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\RegistrationDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property int $actor_id
 * @property RegistrationStatus $status
 * @property string|null $reason
 */
#[Fillable(['student_profile_id', 'actor_id', 'status', 'reason'])]
class RegistrationDecision extends Model
{
    /** @use HasFactory<RegistrationDecisionFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
        ];
    }
}
