<?php

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('notices table contains the required columns', function () {
    expect(Schema::hasColumns('notices', [
        'id',
        'title',
        'body',
        'category',
        'attachment_path',
        'status',
        'is_pinned',
        'published_at',
        'created_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('notice enums expose the documented categories and statuses', function () {
    expect(array_column(NoticeCategory::cases(), 'value'))->toBe([
        'provost_office',
        'hall_office',
        'club',
    ]);

    expect(array_column(NoticeStatus::cases(), 'value'))->toBe([
        'draft',
        'published',
    ]);
});

test('notices declare indexes used by administrative and public queries', function () {
    $indexNames = collect(Schema::getIndexes('notices'))->pluck('name');

    expect($indexNames)->toContain(
        'notices_category_index',
        'notices_created_by_index',
        'notices_status_is_pinned_published_at_id_index',
    );
});

test('notices define the creator foreign key', function () {
    $foreignKeys = collect(Schema::getForeignKeys('notices'));

    expect($foreignKeys->firstWhere('columns', ['created_by']))->not->toBeNull();
});
