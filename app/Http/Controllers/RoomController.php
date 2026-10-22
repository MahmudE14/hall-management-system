<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accommodation\StoreRoomRequest;
use App\Http\Requests\Accommodation\UpdateRoomRequest;
use App\Models\Block;
use App\Models\Room;
use App\Models\Seat;
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
                ->get()
                ->map(fn (Room $room) => $this->serializeRoom($room))
                ->values()
                ->all(),
            'canManage' => Gate::allows('create', Room::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Room::class);

        return Inertia::render('rooms/create', [
            'blocks' => $this->blockOptions(),
        ]);
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
            'room' => $this->serializeRoom($room->load(['block', 'seats'])),
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room updated.')]);

        return to_route('rooms.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'block_id' => $room->block_id,
            'room_no' => $room->room_no,
            'floor' => $room->floor,
            'capacity' => $room->capacity,
            'seats_count' => $room->seats->count(),
            'occupancy_available' => false,
            'occupied_seats' => null,
            'free_seats' => null,
            'block' => $room->block?->only(['id', 'name', 'code']),
            'seats' => $room->seats
                ->sortBy('label')
                ->map(fn (Seat $seat) => $seat->only(['id', 'label']))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blockOptions(): array
    {
        return Block::query()
            ->select(['id', 'name', 'code'])
            ->orderBy('name')
            ->get()
            ->map(fn (Block $block) => $block->only(['id', 'name', 'code']))
            ->values()
            ->all();
    }
}
