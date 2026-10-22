<?php

namespace App\Http\Controllers;

use App\Actions\Notices\CreateNotice;
use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Http\Requests\Notices\StoreNoticeRequest;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Notice::class);

        /** @var User $user */
        $user = $request->user();

        $notices = Notice::query()
            ->with('creator')
            ->when(
                $user->hasRole('club_admin')
                    && ! $user->hasAnyRole('super_admin', 'provost', 'hall_office_staff'),
                fn (Builder $query) => $query->where('created_by', $user->id),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Notice $notice): array => $this->serializeNotice($notice));

        return Inertia::render('admin/notices/index', [
            'notices' => $notices,
            'can_create' => Gate::allows('create', Notice::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Notice::class);

        $categories = collect(NoticeCategory::cases())
            ->filter(fn (NoticeCategory $category): bool => Gate::allows(
                'createInCategory',
                [Notice::class, $category],
            ))
            ->map(fn (NoticeCategory $category): string => $category->value)
            ->values()
            ->all();

        return Inertia::render('admin/notices/create', [
            'categories' => $categories,
            'statuses' => array_column(NoticeStatus::cases(), 'value'),
        ]);
    }

    public function store(
        StoreNoticeRequest $request,
        CreateNotice $createNotice,
    ): RedirectResponse {
        Gate::authorize('create', Notice::class);

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $attachment = $request->file('attachment');

        $createNotice->handle(
            $user,
            [
                'title' => (string) $data['title'],
                'body' => (string) $data['body'],
                'category' => NoticeCategory::from((string) $data['category']),
                'status' => NoticeStatus::from((string) $data['status']),
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            ],
            $attachment instanceof UploadedFile ? $attachment : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notice created.')]);

        return to_route('admin.notices.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNotice(Notice $notice): array
    {
        return [
            'id' => $notice->id,
            'title' => $notice->title,
            'category' => $notice->category->value,
            'status' => $notice->status->value,
            'is_pinned' => $notice->is_pinned,
            'published_at' => $notice->published_at?->toIso8601String(),
            'created_at' => $notice->created_at->toIso8601String(),
            'has_attachment' => $notice->attachment_path !== null,
            'creator' => [
                'id' => $notice->creator->id,
                'name' => $notice->creator->name,
            ],
        ];
    }
}
