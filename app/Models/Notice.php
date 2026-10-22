<?php

namespace App\Models;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property NoticeCategory $category
 * @property string|null $attachment_path
 * @property NoticeStatus $status
 * @property bool $is_pinned
 * @property Carbon|null $published_at
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'title',
    'body',
    'category',
    'attachment_path',
    'status',
    'is_pinned',
    'published_at',
    'created_by',
])]
class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => NoticeCategory::class,
            'status' => NoticeStatus::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
