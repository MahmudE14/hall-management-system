<?php

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('notice factories persist draft records with documented defaults', function () {
    $notice = Notice::factory()->create();

    expect($notice->refresh())
        ->title->not->toBeEmpty()
        ->body->not->toBeEmpty()
        ->category->toBeInstanceOf(NoticeCategory::class)
        ->status->toBe(NoticeStatus::Draft)
        ->is_pinned->toBeFalse()
        ->published_at->toBeNull()
        ->attachment_path->toBeNull()
        ->created_by->not->toBeNull();
});

test('the published factory state sets status and publication time', function () {
    $notice = Notice::factory()->published()->create();

    expect($notice->refresh())
        ->status->toBe(NoticeStatus::Published)
        ->published_at->not->toBeNull();
});

test('the pinned factory state marks a notice as pinned', function () {
    $notice = Notice::factory()->pinned()->create();

    expect($notice->refresh()->is_pinned)->toBeTrue();
});

test('a notice belongs to its creator', function () {
    $creator = User::factory()->create();
    $notice = Notice::factory()->for($creator, 'creator')->create();

    expect($notice->creator->is($creator))->toBeTrue();
    expect($creator->createdNotices()->pluck('id')->all())->toContain($notice->id);
});

test('notice attributes are cast to enums and booleans', function () {
    $notice = Notice::factory()->create([
        'category' => NoticeCategory::HallOffice->value,
        'status' => NoticeStatus::Published->value,
        'is_pinned' => true,
        'published_at' => now(),
    ]);

    expect($notice->category)->toBe(NoticeCategory::HallOffice)
        ->and($notice->status)->toBe(NoticeStatus::Published)
        ->and($notice->is_pinned)->toBeTrue()
        ->and($notice->published_at)->not->toBeNull();
});
