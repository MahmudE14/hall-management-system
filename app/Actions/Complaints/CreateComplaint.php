<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\StudentProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateComplaint
{
    public function __construct(
        private readonly GenerateComplaintTrackingNumber $trackingNumbers,
    ) {}

    /**
     * @param  array{category: ComplaintCategory, title: string, description: string, location?: string|null}  $attributes
     */
    public function handle(
        StudentProfile $studentProfile,
        array $attributes,
        ?UploadedFile $image = null,
    ): Complaint {
        $imagePath = $image?->store('complaints', 'local') ?: null;

        try {
            return DB::transaction(function () use ($studentProfile, $attributes, $imagePath): Complaint {
                $activeAllocation = $studentProfile->activeSeatAllocation()->with('seat')->first();

                return $studentProfile->complaints()->create([
                    'tracking_id' => $this->trackingNumbers->handle(),
                    'room_id' => $activeAllocation?->seat?->room_id,
                    'location' => $activeAllocation === null ? ($attributes['location'] ?? null) : null,
                    'category' => $attributes['category'],
                    'title' => $attributes['title'],
                    'description' => $attributes['description'],
                    'image_path' => $imagePath,
                    'status' => ComplaintStatus::Submitted,
                ]);
            });
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('local')->delete($imagePath);
            }

            throw $exception;
        }
    }
}
