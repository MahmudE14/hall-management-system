<?php

use App\Enums\NoticeCategory;
use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests see only published notices pinned first then newest first', function () {
    $newestUnpinned = Notice::factory()->published()->create([
        'title' => 'Newest unpinned',
        'published_at' => now()->subMinutes(10),
    ]);
    $olderUnpinned = Notice::factory()->published()->create([
        'title' => 'Older unpinned',
        'published_at' => now()->subHour(),
    ]);
    $newerPinned = Notice::factory()->published()->pinned()->create([
        'title' => 'Newer pinned',
        'published_at' => now()->subMinutes(20),
    ]);
    $olderPinned = Notice::factory()->published()->pinned()->create([
        'title' => 'Older pinned',
        'published_at' => now()->subHours(2),
    ]);
    $pinnedDraft = Notice::factory()->pinned()->create([
        'title' => 'Pinned draft',
        'published_at' => null,
    ]);

    $this->get(route('notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('notices/index', false)
            ->has('notices.data', 4)
            ->where('notices.total', 4)
            ->where('notices.data', fn ($notices): bool => collect($notices)->pluck('id')->all() === [
                $newerPinned->id,
                $olderPinned->id,
                $newestUnpinned->id,
                $olderUnpinned->id,
            ] && ! collect($notices)->pluck('id')->contains($pinnedDraft->id))
        );
});

test('published notices with the same publication time are ordered by descending id', function () {
    $publishedAt = now()->subDay();
    $first = Notice::factory()->published()->create(['published_at' => $publishedAt]);
    $second = Notice::factory()->published()->create(['published_at' => $publishedAt]);

    $this->get(route('notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('notices.data.0.id', $second->id)
            ->where('notices.data.1.id', $first->id)
        );
});

test('draft notices never appear in public lists, searches, or pagination totals', function () {
    Notice::factory()->published()->create(['title' => 'Published timetable']);
    Notice::factory()->create(['title' => 'Draft timetable']);

    $this->get(route('notices.index', ['search' => 'timetable']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notices.data', 1)
            ->where('notices.total', 1)
            ->where('notices.data.0.title', 'Published timetable')
        );

    foreach (range(1, 20) as $index) {
        Notice::factory()->create(['title' => "Draft {$index}"]);
    }

    $this->get(route('notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notices.data', 1)
            ->where('notices.total', 1)
            ->where('notices.last_page', 1)
        );
});

test('the public list searches published notice titles and filters by category', function () {
    $matching = Notice::factory()->published()->create([
        'title' => 'Hall week schedule',
        'category' => NoticeCategory::HallOffice,
    ]);
    Notice::factory()->published()->create([
        'title' => 'Hall week schedule for clubs',
        'category' => NoticeCategory::Club,
    ]);
    Notice::factory()->published()->create([
        'title' => 'Provost office hours',
        'category' => NoticeCategory::ProvostOffice,
    ]);
    Notice::factory()->create([
        'title' => 'Hall week schedule draft',
        'category' => NoticeCategory::HallOffice,
    ]);

    $this->get(route('notices.index', [
        'search' => 'Hall week',
        'category' => NoticeCategory::HallOffice->value,
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notices.data', 1)
            ->where('notices.data.0.id', $matching->id)
            ->where('filters.search', 'Hall week')
            ->where('filters.category', NoticeCategory::HallOffice->value)
            ->where('categories', array_column(NoticeCategory::cases(), 'value'))
        );
});

test('the public list paginates published notices without counting drafts', function () {
    Notice::factory()->published()->count(21)->create();
    Notice::factory()->count(5)->create();

    $this->get(route('notices.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notices.data', 20)
            ->where('notices.total', 21)
            ->where('notices.last_page', 2)
            ->where('notices.current_page', 1)
        );

    $this->get(route('notices.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notices.data', 1)
            ->where('notices.total', 21)
        );
});

test('guests can read a published notice detail', function () {
    $notice = Notice::factory()->published()->pinned()->create([
        'title' => 'Published notice',
        'body' => 'The full published notice body.',
        'category' => NoticeCategory::ProvostOffice,
        'attachment_path' => 'notices/generated.pdf',
    ]);

    $this->get(route('notices.show', $notice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('notices/show', false)
            ->where('notice.id', $notice->id)
            ->where('notice.title', 'Published notice')
            ->where('notice.body', 'The full published notice body.')
            ->where('notice.category', NoticeCategory::ProvostOffice->value)
            ->where('notice.is_pinned', true)
            ->where('notice.has_attachment', true)
        );
});

test('a direct draft notice detail request returns not found', function () {
    $draft = Notice::factory()->create(['title' => 'Draft notice']);

    $this->get(route('notices.show', $draft))->assertNotFound();
});

test('an unknown public notice detail request returns not found', function () {
    $this->get(route('notices.show', 999999))->assertNotFound();
});
