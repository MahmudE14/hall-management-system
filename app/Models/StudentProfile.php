<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $registration_number
 * @property string $department
 * @property string $session
 * @property string $phone
 * @property RegistrationStatus $registration_status
 * @property ResidencyStatus $residency_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'registration_number',
    'department',
    'session',
    'phone',
    'registration_status',
    'residency_status',
])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<RegistrationDecision, $this> */
    public function registrationDecisions(): HasMany
    {
        return $this->hasMany(RegistrationDecision::class)->latest();
    }

    /** @return HasMany<SeatAllocation, $this> */
    public function seatAllocations(): HasMany
    {
        return $this->hasMany(SeatAllocation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'registration_status' => RegistrationStatus::class,
            'residency_status' => ResidencyStatus::class,
        ];
    }
}
