<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int $block_id
 * @property string $room_no
 * @property int $floor
 * @property int $capacity
 * @property-read int|null $occupied_seats_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['room_no', 'floor', 'capacity'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Room $room): void {
            if ($room->capacity < 1) {
                throw new InvalidArgumentException('Room capacity must be at least 1.');
            }
        });
    }

    /** @return BelongsTo<Block, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /** @return HasMany<Seat, $this> */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /** @return HasManyThrough<SeatAllocation, Seat, $this> */
    public function seatAllocations(): HasManyThrough
    {
        return $this->hasManyThrough(SeatAllocation::class, Seat::class);
    }

    /** @return HasMany<Complaint, $this> */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'capacity' => 'integer',
        ];
    }
}
