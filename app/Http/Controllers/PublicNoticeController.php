<?php

namespace App\Http\Controllers;

use App\Enums\NoticeCategory;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
            ->findOrFail($notice->getKey());

        return Inertia::render('notices/show', [
            'notice' => $this->serializeNotice($notice, includeBody: true),
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
