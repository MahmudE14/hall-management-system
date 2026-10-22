<?php

namespace App\Models;

use Database\Factories\ComplaintCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $complaint_id
 * @property int $author_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['body'])]
class ComplaintComment extends Model
{
    /** @use HasFactory<ComplaintCommentFactory> */
    use HasFactory;

    /** @return BelongsTo<Complaint, $this> */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
