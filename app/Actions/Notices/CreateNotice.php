<?php

namespace App\Actions\Notices;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateNotice
{
    public function __construct(
        private readonly StoreNoticeAttachment $attachments,
    ) {}

    /**
     * @param  array{title: string, body: string, category: NoticeCategory, status: NoticeStatus, is_pinned?: bool}  $attributes
     */
    public function handle(
        User $creator,
        array $attributes,
        ?UploadedFile $attachment = null,
    ): Notice {
        $attachmentPath = $attachment === null ? null : $this->attachments->store($attachment);

        try {
            return DB::transaction(fn (): Notice => $creator->createdNotices()->create([
                'title' => $attributes['title'],
                'body' => $attributes['body'],
                'category' => $attributes['category'],
                'attachment_path' => $attachmentPath,
                'status' => $attributes['status'],
                'is_pinned' => $attributes['is_pinned'] ?? false,
                'published_at' => $attributes['status'] === NoticeStatus::Published ? now() : null,
            ]));
        } catch (Throwable $exception) {
            $this->attachments->delete($attachmentPath);

            throw $exception;
        }
    }
}
