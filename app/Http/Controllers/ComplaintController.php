<?php

namespace App\Http\Controllers;

use App\Actions\Complaints\CreateComplaint;
use App\Enums\ComplaintCategory;
use App\Http\Requests\Complaints\StoreComplaintRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if ($studentProfile === null) {
            abort(403);
        }

        $activeAllocation = $studentProfile->activeSeatAllocation()
            ->with('seat.room.block')
            ->first();
        $room = $activeAllocation?->seat?->room;

        return Inertia::render('complaints/create', [
            'currentRoom' => $room === null ? null : [
                'room_no' => $room->room_no,
                'block' => $room->block?->name,
            ],
            'requiresLocation' => $room === null,
            'categories' => array_column(ComplaintCategory::cases(), 'value'),
        ]);
    }

    public function store(
        StoreComplaintRequest $request,
        CreateComplaint $createComplaint,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if ($studentProfile === null) {
            abort(403);
        }

        $image = $request->file('image');

        $complaint = $createComplaint->handle(
            $studentProfile,
            $request->validated(),
            $image instanceof UploadedFile ? $image : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Complaint submitted. Tracking ID: :tracking_id', [
                'tracking_id' => $complaint->tracking_id,
            ]),
        ]);

        return to_route('dashboard');
    }
}
