<?php

namespace App\Models;

use Database\Factories\SeatAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property int $seat_id
 * @property Carbon $allocated_at
 * @property Carbon|null $vacated_at
 * @property string|null $vacancy_reason
 * @property bool|null $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['seat_id', 'allocated_at', 'vacated_at', 'vacancy_reason', 'active'])]
class SeatAllocation extends Model
{
    /** @use HasFactory<SeatAllocationFactory> */
    use HasFactory;

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<Seat, $this> */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'allocated_at' => 'date',
            'vacated_at' => 'date',
            'active' => 'boolean',
        ];
    }
}
