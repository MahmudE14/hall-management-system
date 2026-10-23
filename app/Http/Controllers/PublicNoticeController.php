<?php

namespace App\Http\Controllers;

use App\Enums\NoticeCategory;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicNoticeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(NoticeCategory::class)],
        ]);

        $notices = Notice::query()
            ->published()
            ->with('creator')
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where('title', 'like', "%{$search}%"),
            )
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, string $category) => $query->where('category', $category),
            )
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Notice $notice): array => $this->serializeNotice($notice));

        return Inertia::render('notices/index', [
            'notices' => $notices,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'category' => $filters['category'] ?? null,
            ],
            'categories' => array_column(NoticeCategory::cases(), 'value'),
        ]);
    }

    public function show(Notice $notice): Response
    {
        $notice = Notice::query()
            ->published()
            ->with('creator')
            ->whereKey($notice->getKey())
            ->firstOrFail();

        return Inertia::render('notices/show', [
            'notice' => $this->serializeNotice($notice, includeBody: true),
        ]);
    }

    public function attachment(Notice $notice): StreamedResponse
    {
        $notice = Notice::query()
            ->published()
            ->whereKey($notice->getKey())
            ->firstOrFail();

        $path = $notice->attachment_path;

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, "notice-{$notice->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNotice(Notice $notice, bool $includeBody = false): array
    {
        return [
            'id' => $notice->id,
            'title' => $notice->title,
            'category' => $notice->category->value,
            'is_pinned' => $notice->is_pinned,
            'published_at' => $notice->published_at?->toIso8601String(),
            'has_attachment' => $notice->attachment_path !== null,
            'attachment_url' => $notice->attachment_path === null
                ? null
                : route('notices.attachment', $notice),
            'creator' => [
                'id' => $notice->creator->id,
                'name' => $notice->creator->name,
            ],
            ...($includeBody ? [
                'body' => $notice->body,
                'created_at' => $notice->created_at->toIso8601String(),
            ] : []),
        ];
    }
}
