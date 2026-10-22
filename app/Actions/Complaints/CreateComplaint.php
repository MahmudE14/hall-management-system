<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class CreateComplaint
{
    public function __construct(
        private readonly GenerateComplaintTrackingNumber $trackingNumbers,
    ) {}

    /**
     * @param  array{category: string, title: string, description: string, location?: string|null}  $attributes
     */
    public function handle(StudentProfile $studentProfile, array $attributes): Complaint
    {
        return DB::transaction(function () use ($studentProfile, $attributes): Complaint {
            $activeAllocation = $studentProfile->activeSeatAllocation()->with('seat')->first();

            return $studentProfile->complaints()->create([
                'tracking_id' => $this->trackingNumbers->handle(),
                'room_id' => $activeAllocation?->seat?->room_id,
                'location' => $activeAllocation === null ? ($attributes['location'] ?? null) : null,
                'category' => $attributes['category'],
                'title' => $attributes['title'],
                'description' => $attributes['description'],
                'status' => ComplaintStatus::Submitted,
            ]);
        });
    }
}
