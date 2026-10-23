<?php

namespace App\Actions\Notices;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StoreNoticeAttachment
{
    public function store(UploadedFile $attachment): string
    {
        $path = $attachment->storeAs('notices', Str::uuid()->toString().'.pdf', 'local');

        if (! is_string($path)) {
            throw new RuntimeException('The notice attachment could not be stored.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('local')->delete($path);
        }
    }
}
