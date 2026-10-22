<?php

namespace App\Http\Controllers;

use App\Actions\Accommodation\AllocateSeat;
use App\Http\Requests\Accommodation\StoreSeatAllocationRequest;
use App\Models\Seat;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SeatAllocationController extends Controller
{
    public function store(
        StoreSeatAllocationRequest $request,
        StudentProfile $studentProfile,
        AllocateSeat $allocateSeat,
    ): RedirectResponse {
        $allocateSeat->handle(
            $studentProfile,
            Seat::query()->findOrFail($request->integer('seat_id')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Seat allocated.')]);

        return to_route('students.show', $studentProfile);
    }
}
