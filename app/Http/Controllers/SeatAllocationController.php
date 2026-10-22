<?php

namespace App\Http\Controllers;

use App\Actions\Accommodation\AllocateSeat;
use App\Actions\Accommodation\VacateSeat;
use App\Http\Requests\Accommodation\StoreSeatAllocationRequest;
use App\Http\Requests\Accommodation\VacateSeatRequest;
use App\Models\Seat;
use App\Models\SeatAllocation;
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

    public function vacate(
        VacateSeatRequest $request,
        SeatAllocation $seatAllocation,
        VacateSeat $vacateSeat,
    ): RedirectResponse {
        $vacateSeat->handle(
            $seatAllocation,
            $request->string('vacated_at')->toString(),
            $request->string('vacancy_reason')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Seat vacated.')]);

        return to_route('students.show', $seatAllocation->student_profile_id);
    }
}
