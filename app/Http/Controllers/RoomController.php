<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accommodation\StoreRoomRequest;
use App\Http\Requests\Accommodation\UpdateRoomRequest;
use App\Models\Block;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Room::class);

        return Inertia::render('rooms/index', [
            'rooms' => Room::query()
                ->with(['block', 'seats'])
                ->orderBy('room_no')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Room::class);

        return Inertia::render('rooms/create');
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $block = Block::query()->findOrFail($request->integer('block_id'));

        $block->rooms()->create($request->safe()->only(['room_no', 'floor', 'capacity']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room created.')]);

        return to_route('rooms.index');
    }

    public function edit(Room $room): Response
    {
        Gate::authorize('update', $room);

        return Inertia::render('rooms/edit', [
            'room' => $room->load(['block', 'seats']),
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room updated.')]);

        return to_route('rooms.index');
    }
}
