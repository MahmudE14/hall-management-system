<?php

namespace App\Http\Controllers;

use App\Actions\Complaints\AssignComplaint;
use App\Actions\Complaints\CreateComplaint;
use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Enums\RegistrationStatus;
use App\Http\Requests\Complaints\AssignComplaintRequest;
use App\Http\Requests\Complaints\StoreComplaintCommentRequest;
use App\Http\Requests\Complaints\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Complaint::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ComplaintStatus::class)],
            'category' => ['nullable', Rule::enum(ComplaintCategory::class)],
        ]);

        /** @var User $user */
        $user = $request->user();

        $complaints = Complaint::query()
            ->visibleTo($user)
            ->with(['studentProfile.user', 'room.block'])
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status),
            )
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, string $category) => $query->where('category', $category),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Complaint $complaint) => $this->serializeComplaint($complaint));

        return Inertia::render('complaints/index', [
            'complaints' => $complaints,
            'filters' => [
                'status' => $filters['status'] ?? null,
                'category' => $filters['category'] ?? null,
            ],
            'statuses' => array_column(ComplaintStatus::cases(), 'value'),
            'categories' => array_column(ComplaintCategory::cases(), 'value'),
            'can_create' => $user->hasRole('student')
                && $user->studentProfile?->registration_status === RegistrationStatus::Approved,
        ]);
    }

    public function show(Complaint $complaint): Response
    {
        Gate::authorize('view', $complaint);

        return Inertia::render('complaints/show', [
            'complaint' => $this->serializeComplaint(
                $complaint,
                includeDescription: true,
                includeComments: true,
                includeTimeline: true,
            ),
        ]);
    }

    public function image(Complaint $complaint): StreamedResponse
    {
        Gate::authorize('view', $complaint);

        $path = $complaint->image_path;

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }

    public function assign(
        AssignComplaintRequest $request,
        Complaint $complaint,
        AssignComplaint $assignComplaint,
    ): RedirectResponse {
        Gate::authorize('assign', $complaint);

        $assignee = User::query()->findOrFail((int) $request->validated('assignee_id'));

        $assignComplaint->handle($complaint, $assignee);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Complaint assigned.')]);

        return to_route('complaints.show', $complaint);
    }

    public function comment(
        StoreComplaintCommentRequest $request,
        Complaint $complaint,
    ): RedirectResponse {
        Gate::authorize('comment', $complaint);

        /** @var User $user */
        $user = $request->user();

        $comment = new ComplaintComment(['body' => (string) $request->validated('body')]);
        $comment->complaint()->associate($complaint);
        $comment->author()->associate($user);
        $comment->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return to_route('complaints.show', $complaint);
    }

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

        $data = $request->validated();
        $image = $request->file('image');

        $complaint = $createComplaint->handle(
            $studentProfile,
            [
                'category' => ComplaintCategory::from((string) $data['category']),
                'title' => (string) $data['title'],
                'description' => (string) $data['description'],
                'location' => isset($data['location']) ? (string) $data['location'] : null,
            ],
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

    /**
     * @return array<string, mixed>
     */
    private function serializeComplaint(
        Complaint $complaint,
        bool $includeDescription = false,
        bool $includeComments = false,
        bool $includeTimeline = false,
    ): array {
        $complaint->loadMissing(['studentProfile.user', 'room.block', 'assignee']);

        if ($includeComments) {
            $complaint->loadMissing('comments.author');
        }

        if ($includeTimeline) {
            $complaint->loadMissing('statusLogs.actor');
        }

        $room = $complaint->room;

        return [
            'id' => $complaint->id,
            'tracking_id' => $complaint->tracking_id,
            'category' => $complaint->category->value,
            'title' => $complaint->title,
            'status' => $complaint->status->value,
            'location' => $complaint->location,
            'room_no' => $room?->room_no,
            'block' => $room?->block?->name,
            'student_name' => $complaint->studentProfile?->user?->name,
            'registration_number' => $complaint->studentProfile?->registration_number,
            'days_open' => $complaint->daysOpen(),
            'is_overdue' => $complaint->isOverdue(),
            'created_at' => $complaint->created_at->toIso8601String(),
            'assignee' => $complaint->assignee === null ? null : [
                'id' => $complaint->assignee->id,
                'name' => $complaint->assignee->name,
            ],
            'assigned_at' => $complaint->assigned_at?->toIso8601String(),
            'image_url' => $complaint->image_path === null
                ? null
                : route('complaints.image', $complaint),
            ...($includeDescription ? ['description' => $complaint->description] : []),
            ...($includeComments ? [
                'comments' => $complaint->comments
                    ->map(fn (ComplaintComment $comment): array => [
                        'id' => $comment->id,
                        'author' => [
                            'id' => $comment->author->id,
                            'name' => $comment->author->name,
                        ],
                        'body' => $comment->body,
                        'created_at' => $comment->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ] : []),
            ...($includeTimeline ? [
                'status_history' => $complaint->statusLogs
                    ->map(fn (ComplaintStatusLog $log): array => [
                        'id' => $log->id,
                        'from_status' => $log->from_status?->value,
                        'status' => $log->status->value,
                        'note' => $log->note,
                        'actor' => $log->actor === null ? null : [
                            'id' => $log->actor->id,
                            'name' => $log->actor->name,
                        ],
                        'created_at' => $log->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ] : []),
        ];
    }
}
