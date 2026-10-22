<?php

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('complaint tables contain the required columns', function () {
    expect(Schema::hasColumns('complaints', [
        'id',
        'tracking_id',
        'student_profile_id',
        'room_id',
        'location',
        'category',
        'title',
        'description',
        'image_path',
        'status',
        'assignee_id',
        'assigned_at',
        'resolved_at',
        'resolution_note',
        'rejection_reason',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('complaint_status_logs', [
        'id',
        'complaint_id',
        'actor_id',
        'from_status',
        'status',
        'note',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('complaint_comments', [
        'id',
        'complaint_id',
        'author_id',
        'body',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('complaint enums expose the documented statuses and categories', function () {
    expect(array_column(ComplaintStatus::cases(), 'value'))->toBe([
        'submitted',
        'under_review',
        'in_progress',
        'resolved',
        'closed',
        'rejected',
    ]);

    expect(array_column(ComplaintCategory::cases(), 'value'))->toBe([
        'water',
        'electricity',
        'internet',
        'cleanliness',
        'furniture',
        'security',
        'other',
    ]);
});

test('complaints enforce a unique tracking identifier', function () {
    $trackingIndex = collect(Schema::getIndexes('complaints'))
        ->firstWhere('name', 'complaints_tracking_id_unique');

    expect($trackingIndex)->not->toBeNull();
    expect($trackingIndex['unique'])->toBeTrue();
    expect($trackingIndex['columns'])->toBe(['tracking_id']);
});

test('complaints declare indexes used by list filters and aging queries', function () {
    $indexNames = collect(Schema::getIndexes('complaints'))->pluck('name');

    expect($indexNames)->toContain(
        'complaints_student_profile_id_index',
        'complaints_category_index',
        'complaints_status_index',
        'complaints_assignee_id_index',
        'complaints_created_at_index',
        'complaints_resolved_at_index',
    );
});

test('complaint status logs and comments index complaint timelines', function () {
    $statusLogIndexNames = collect(Schema::getIndexes('complaint_status_logs'))->pluck('name');
    $commentIndexNames = collect(Schema::getIndexes('complaint_comments'))->pluck('name');

    expect($statusLogIndexNames)->toContain('complaint_status_logs_complaint_id_created_at_index');
    expect($commentIndexNames)->toContain('complaint_comments_complaint_id_created_at_index');
});

test('complaint tables define the required foreign keys', function () {
    $complaintForeignKeys = collect(Schema::getForeignKeys('complaints'));
    $statusLogForeignKeys = collect(Schema::getForeignKeys('complaint_status_logs'));
    $commentForeignKeys = collect(Schema::getForeignKeys('complaint_comments'));

    expect($complaintForeignKeys->firstWhere('columns', ['student_profile_id']))->not->toBeNull();
    expect($complaintForeignKeys->firstWhere('columns', ['room_id']))->not->toBeNull();
    expect($complaintForeignKeys->firstWhere('columns', ['assignee_id']))->not->toBeNull();
    expect($statusLogForeignKeys->firstWhere('columns', ['complaint_id']))->not->toBeNull();
    expect($statusLogForeignKeys->firstWhere('columns', ['actor_id']))->not->toBeNull();
    expect($commentForeignKeys->firstWhere('columns', ['complaint_id']))->not->toBeNull();
    expect($commentForeignKeys->firstWhere('columns', ['author_id']))->not->toBeNull();
});
