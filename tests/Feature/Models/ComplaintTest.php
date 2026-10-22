<?php

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintStatusLog;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('complaint factories persist records with documented defaults', function () {
    $complaint = Complaint::factory()->create();

    expect($complaint->refresh())
        ->tracking_id->toMatch('/^KJH-\d{4}-\d{4}$/')
        ->category->toBeInstanceOf(ComplaintCategory::class)
        ->status->toBe(ComplaintStatus::Submitted)
        ->location->toBeNull()
        ->image_path->toBeNull()
        ->assignee_id->toBeNull()
        ->assigned_at->toBeNull()
        ->resolved_at->toBeNull()
        ->resolution_note->toBeNull()
        ->rejection_reason->toBeNull();
});

test('a complaint belongs to a student profile and a room', function () {
    $student = StudentProfile::factory()->create();
    $room = Room::factory()->create();
    $complaint = Complaint::factory()->for($student)->for($room)->create();

    expect($complaint->studentProfile->is($student))->toBeTrue();
    expect($complaint->room->is($room))->toBeTrue();
    expect($student->complaints()->pluck('id')->all())->toContain($complaint->id);
    expect($room->complaints()->pluck('id')->all())->toContain($complaint->id);
});

test('a complaint may be assigned to a user', function () {
    $assignee = User::factory()->create();
    $complaint = Complaint::factory()->for($assignee, 'assignee')->create([
        'assigned_at' => now(),
    ]);

    expect($complaint->assignee->is($assignee))->toBeTrue();
    expect($assignee->assignedComplaints()->pluck('id')->all())->toContain($complaint->id);
    expect($complaint->assigned_at)->not->toBeNull();
});

test('complaints have status logs and comments in insertion order', function () {
    $complaint = Complaint::factory()->create();
    $firstLog = ComplaintStatusLog::factory()->for($complaint)->create(['created_at' => now()->subMinutes(2)]);
    $secondLog = ComplaintStatusLog::factory()->for($complaint)->create(['created_at' => now()->subMinute()]);
    $comment = ComplaintComment::factory()->for($complaint)->create();

    expect($complaint->statusLogs()->pluck('id')->all())->toBe([$firstLog->id, $secondLog->id]);
    expect($complaint->comments()->pluck('id')->all())->toBe([$comment->id]);
});

test('status log factories persist actor and status details', function () {
    $complaint = Complaint::factory()->create();
    $actor = User::factory()->create();
    $log = ComplaintStatusLog::factory()->for($complaint)->for($actor, 'actor')->create([
        'from_status' => ComplaintStatus::Submitted,
        'status' => ComplaintStatus::UnderReview,
        'note' => 'Review started.',
    ]);

    expect($log->refresh())
        ->complaint_id->toBe($complaint->id)
        ->actor_id->toBe($actor->id)
        ->from_status->toBe(ComplaintStatus::Submitted)
        ->status->toBe(ComplaintStatus::UnderReview)
        ->note->toBe('Review started.');
    expect($log->complaint->is($complaint))->toBeTrue();
    expect($log->actor->is($actor))->toBeTrue();
});

test('comment factories persist the author and body', function () {
    $complaint = Complaint::factory()->create();
    $author = User::factory()->create();
    $comment = ComplaintComment::factory()->for($complaint)->for($author, 'author')->create([
        'body' => 'The tap is still leaking.',
    ]);

    expect($comment->refresh())
        ->complaint_id->toBe($complaint->id)
        ->author_id->toBe($author->id)
        ->body->toBe('The tap is still leaking.');
    expect($comment->complaint->is($complaint))->toBeTrue();
    expect($comment->author->is($author))->toBeTrue();
    expect($author->complaintComments()->pluck('id')->all())->toContain($comment->id);
});

test('days open is calculated from the submission timestamp', function () {
    $this->travelTo(now()->startOfDay());

    $complaint = Complaint::factory()->create([
        'created_at' => now()->subDays(8),
    ]);

    expect($complaint->daysOpen())->toBe(8);
});

test('complaints older than seven days in active statuses are overdue', function (ComplaintStatus $status, bool $expected) {
    $this->travelTo(now()->startOfDay());

    $complaint = Complaint::factory()->create([
        'status' => $status,
        'created_at' => now()->subDays(8),
    ]);

    expect($complaint->isOverdue())->toBe($expected);
})->with([
    'submitted' => [ComplaintStatus::Submitted, true],
    'under review' => [ComplaintStatus::UnderReview, true],
    'in progress' => [ComplaintStatus::InProgress, true],
    'resolved' => [ComplaintStatus::Resolved, false],
    'closed' => [ComplaintStatus::Closed, false],
    'rejected' => [ComplaintStatus::Rejected, false],
]);

test('a complaint exactly seven days old is not overdue', function () {
    $this->travelTo(now()->startOfDay());

    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::Submitted,
        'created_at' => now()->subDays(7),
    ]);

    expect($complaint->daysOpen())->toBe(7);
    expect($complaint->isOverdue())->toBeFalse();
});
