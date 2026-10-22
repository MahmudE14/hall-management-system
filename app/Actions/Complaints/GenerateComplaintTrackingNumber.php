<?php

namespace App\Actions\Complaints;

use App\Models\ComplaintTrackingCounter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class GenerateComplaintTrackingNumber
{
    public function handle(?int $year = null): string
    {
        $year ??= now()->year;

        return DB::transaction(function () use ($year): string {
            $counter = ComplaintTrackingCounter::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                try {
                    $counter = ComplaintTrackingCounter::query()->create([
                        'year' => $year,
                        'last_number' => 0,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $counter = ComplaintTrackingCounter::query()
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $counter->increment('last_number');

            return sprintf('KJH-%d-%04d', $year, $counter->last_number);
        });
    }
}
