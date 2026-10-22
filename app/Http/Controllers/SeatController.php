<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accommodation\StoreSeatRequest;
use App\Http\Requests\Accommodation\UpdateSeatRequest;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SeatController extends Controller
{
    public function store(StoreSeatRequest $request, Room $room): RedirectResponse
    {
        $room->seats()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Seat created.')]);

        return to_route('rooms.edit', $room);
    }

    public function update(UpdateSeatRequest $request, Seat $seat): RedirectResponse
    {
        $seat->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Seat updated.')]);

        return to_route('rooms.edit', $seat->room);
    }
}
