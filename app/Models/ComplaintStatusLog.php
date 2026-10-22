<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Database\Factories\ComplaintStatusLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $complaint_id
 * @property int|null $actor_id
 * @property ComplaintStatus|null $from_status
 * @property ComplaintStatus $status
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['from_status', 'status', 'note'])]
class ComplaintStatusLog extends Model
{
    /** @use HasFactory<ComplaintStatusLogFactory> */
    use HasFactory;

    /** @return BelongsTo<Complaint, $this> */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
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
            'from_status' => ComplaintStatus::class,
            'status' => ComplaintStatus::class,
        ];
    }
}
