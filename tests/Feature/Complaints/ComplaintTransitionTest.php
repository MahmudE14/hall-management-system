<?php

use App\Actions\Complaints\TransitionComplaint;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintStatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('documented forward transitions update status and append one audit entry', function (ComplaintStatus $from, ComplaintStatus $to) {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => $from]);

    $transitioned = app(TransitionComplaint::class)->handle(
        $complaint,
        $to,
        $actor,
        'Status update.',
    );

    $log = $complaint->statusLogs()->sole();

    expect($transitioned->status)->toBe($to)
        ->and($complaint->refresh()->status)->toBe($to)
        ->and($log->from_status)->toBe($from)
        ->and($log->status)->toBe($to)
        ->and($log->actor_id)->toBe($actor->id)
        ->and($log->note)->toBe('Status update.')
        ->and($log->created_at)->not->toBeNull();
})->with([
    'submitted to under review' => [ComplaintStatus::Submitted, ComplaintStatus::UnderReview],
    'under review to in progress' => [ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
    'in progress to resolved' => [ComplaintStatus::InProgress, ComplaintStatus::Resolved],
    'resolved to closed' => [ComplaintStatus::Resolved, ComplaintStatus::Closed],
    'resolved reopens to in progress' => [ComplaintStatus::Resolved, ComplaintStatus::InProgress],
]);

test('documented rejection transitions record the reason in the audit entry', function (ComplaintStatus $from) {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => $from]);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Rejected,
        $actor,
        'Duplicate complaint.',
    );

    $log = $complaint->statusLogs()->sole();

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Rejected)
        ->and($log->from_status)->toBe($from)
        ->and($log->status)->toBe(ComplaintStatus::Rejected)
        ->and($log->actor_id)->toBe($actor->id)
        ->and($log->note)->toBe('Duplicate complaint.');
})->with([
    'submitted to rejected' => [ComplaintStatus::Submitted],
    'under review to rejected' => [ComplaintStatus::UnderReview],
]);

test('a transition does not depend on the actor having a role', function () {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::UnderReview,
        $actor,
    );

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::UnderReview)
        ->and($complaint->statusLogs()->count())->toBe(1);
});

test('transitions outside the documented graph are refused without changing state or history', function (ComplaintStatus $from, ComplaintStatus $to) {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => $from]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, $to, $actor))
        ->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe($from)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'submitted skips review' => [ComplaintStatus::Submitted, ComplaintStatus::InProgress],
    'submitted skips to resolved' => [ComplaintStatus::Submitted, ComplaintStatus::Resolved],
    'submitted skips to closed' => [ComplaintStatus::Submitted, ComplaintStatus::Closed],
    'under review returns to submitted' => [ComplaintStatus::UnderReview, ComplaintStatus::Submitted],
    'under review skips to resolved' => [ComplaintStatus::UnderReview, ComplaintStatus::Resolved],
    'in progress is rejected' => [ComplaintStatus::InProgress, ComplaintStatus::Rejected],
    'in progress skips to closed' => [ComplaintStatus::InProgress, ComplaintStatus::Closed],
    'resolved returns to under review' => [ComplaintStatus::Resolved, ComplaintStatus::UnderReview],
    'closed cannot change' => [ComplaintStatus::Closed, ComplaintStatus::InProgress],
    'rejected cannot change' => [ComplaintStatus::Rejected, ComplaintStatus::UnderReview],
    'same status is refused' => [ComplaintStatus::Submitted, ComplaintStatus::Submitted],
]);

test('a stale complaint instance cannot overwrite a newer transition', function () {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);
    $staleComplaint = $complaint->fresh();

    $action = app(TransitionComplaint::class);
    $action->handle($complaint, ComplaintStatus::UnderReview, $actor);

    expect(fn () => $action->handle($staleComplaint, ComplaintStatus::UnderReview, $actor))
        ->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::UnderReview)
        ->and($complaint->statusLogs()->count())->toBe(1);
});

test('the status change and audit entry are rolled back together', function () {
    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    ComplaintStatusLog::creating(function (): void {
        throw new RuntimeException('Forced audit failure.');
    });

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::UnderReview,
        $actor,
    ))->toThrow(RuntimeException::class, 'Forced audit failure.');

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('sequential transitions keep a complete ordered audit history', function () {
    $this->travelTo(now()->subMinutes(4));

    $actor = User::factory()->create();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);
    $action = app(TransitionComplaint::class);

    $action->handle($complaint, ComplaintStatus::UnderReview, $actor, 'Review started.');
    $this->travel(1)->minutes();

    $action->handle($complaint, ComplaintStatus::InProgress, $actor, 'Plumber assigned.');
    $this->travel(1)->minutes();

    $action->handle($complaint, ComplaintStatus::Resolved, $actor, 'Tap replaced.');
    $this->travel(1)->minutes();

    $action->handle($complaint, ComplaintStatus::Closed, $actor, 'Confirmed fixed.');

    $logs = $complaint->statusLogs()->get();

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Closed)
        ->and($logs)->toHaveCount(4)
        ->and($logs->pluck('from_status')->all())->toBe([
            ComplaintStatus::Submitted,
            ComplaintStatus::UnderReview,
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
        ])
        ->and($logs->pluck('status')->all())->toBe([
            ComplaintStatus::UnderReview,
            ComplaintStatus::InProgress,
            ComplaintStatus::Resolved,
            ComplaintStatus::Closed,
        ])
        ->and($logs->pluck('note')->all())->toBe([
            'Review started.',
            'Plumber assigned.',
            'Tap replaced.',
            'Confirmed fixed.',
        ])
        ->and($logs->pluck('actor_id')->unique()->all())->toBe([$actor->id]);
});
