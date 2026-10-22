<?php

use App\Actions\Complaints\TransitionComplaint;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintStatusLog;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('staff can perform the documented forward transitions', function (string $role, ComplaintStatus $from, ComplaintStatus $to) {
    $actor = complaintTransitionUserWithRole($role);
    $complaint = Complaint::factory()->create(['status' => $from]);

    app(TransitionComplaint::class)->handle($complaint, $to, $actor, 'Progress note.');

    expect($complaint->refresh()->status)->toBe($to)
        ->and($complaint->statusLogs()->sole()->actor_id)->toBe($actor->id);
})->with([
    'hall office staff moves submitted to review' => ['hall_office_staff', ComplaintStatus::Submitted, ComplaintStatus::UnderReview],
    'hall office staff starts progress' => ['hall_office_staff', ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
    'hall office staff resolves' => ['hall_office_staff', ComplaintStatus::InProgress, ComplaintStatus::Resolved],
    'provost moves submitted to review' => ['provost', ComplaintStatus::Submitted, ComplaintStatus::UnderReview],
    'provost starts progress' => ['provost', ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
    'provost resolves' => ['provost', ComplaintStatus::InProgress, ComplaintStatus::Resolved],
    'super admin moves submitted to review' => ['super_admin', ComplaintStatus::Submitted, ComplaintStatus::UnderReview],
    'super admin starts progress' => ['super_admin', ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
    'super admin resolves' => ['super_admin', ComplaintStatus::InProgress, ComplaintStatus::Resolved],
]);

test('staff can reject submitted and under review complaints with a reason', function (string $role, ComplaintStatus $from) {
    $actor = complaintTransitionUserWithRole($role);
    $complaint = Complaint::factory()->create(['status' => $from]);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Rejected,
        $actor,
        'Duplicate complaint.',
    );

    $log = $complaint->statusLogs()->sole();

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Rejected)
        ->and($complaint->rejection_reason)->toBe('Duplicate complaint.')
        ->and($log->note)->toBe('Duplicate complaint.');
})->with([
    'hall office staff rejects submitted' => ['hall_office_staff', ComplaintStatus::Submitted],
    'hall office staff rejects under review' => ['hall_office_staff', ComplaintStatus::UnderReview],
    'provost rejects submitted' => ['provost', ComplaintStatus::Submitted],
    'provost rejects under review' => ['provost', ComplaintStatus::UnderReview],
    'super admin rejects submitted' => ['super_admin', ComplaintStatus::Submitted],
    'super admin rejects under review' => ['super_admin', ComplaintStatus::UnderReview],
]);

test('only provost and hall office staff may close a resolved complaint', function (string $role) {
    $actor = complaintTransitionUserWithRole($role);
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => now()->subHour(),
    ]);

    app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::Closed, $actor);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Closed)
        ->and($complaint->statusLogs()->count())->toBe(1);
})->with(['provost', 'hall_office_staff']);

test('a super admin cannot close a complaint and no state or history changes', function () {
    $actor = complaintTransitionUserWithRole('super_admin');
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => now()->subHour(),
    ]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::Closed, $actor))
        ->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('an assigned active house tutor can perform only the documented forward transitions', function (ComplaintStatus $from, ComplaintStatus $to) {
    $tutor = complaintTransitionTutor();
    $complaint = Complaint::factory()->create([
        'status' => $from,
        'assignee_id' => $tutor->id,
        'assigned_at' => now()->subHour(),
    ]);

    app(TransitionComplaint::class)->handle($complaint, $to, $tutor, 'Tutor note.');

    expect($complaint->refresh()->status)->toBe($to)
        ->and($complaint->statusLogs()->sole()->actor_id)->toBe($tutor->id);
})->with([
    'submitted to under review' => [ComplaintStatus::Submitted, ComplaintStatus::UnderReview],
    'under review to in progress' => [ComplaintStatus::UnderReview, ComplaintStatus::InProgress],
    'in progress to resolved' => [ComplaintStatus::InProgress, ComplaintStatus::Resolved],
]);

test('an assigned house tutor cannot reject a complaint', function (ComplaintStatus $from) {
    $tutor = complaintTransitionTutor();
    $complaint = Complaint::factory()->create([
        'status' => $from,
        'assignee_id' => $tutor->id,
        'assigned_at' => now()->subHour(),
    ]);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Rejected,
        $tutor,
        'Tutor rejection.',
    ))->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe($from)
        ->and($complaint->rejection_reason)->toBeNull()
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'submitted' => [ComplaintStatus::Submitted],
    'under review' => [ComplaintStatus::UnderReview],
]);

test('an assigned house tutor cannot close or reopen a complaint', function (ComplaintStatus $to) {
    $tutor = complaintTransitionTutor();
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::Resolved,
        'assignee_id' => $tutor->id,
        'assigned_at' => now()->subHour(),
        'resolved_at' => now()->subHour(),
    ]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, $to, $tutor))
        ->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'closed' => [ComplaintStatus::Closed],
    'in progress' => [ComplaintStatus::InProgress],
]);

test('a house tutor cannot transition an unassigned complaint', function () {
    $tutor = complaintTransitionTutor();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::UnderReview, $tutor))
        ->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('an inactive or profile-less house tutor cannot transition an assigned complaint', function (string $state) {
    $tutor = complaintTransitionTutor($state);

    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::Submitted,
        'assignee_id' => $tutor->id,
    ]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::UnderReview, $tutor))
        ->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with(['inactive', 'without profile']);

test('other roles cannot perform staff transitions', function (?string $role) {
    $actor = $role === null
        ? User::factory()->create()
        : complaintTransitionUserWithRole($role);

    if ($role === 'student') {
        StudentProfile::factory()->for($actor)->create();
    }

    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::UnderReview, $actor))
        ->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'student' => ['student'],
    'club admin' => ['club_admin'],
    'no role' => [null],
]);

test('rejecting without a meaningful reason is refused without changing state or history', function (?string $note) {
    $actor = complaintTransitionUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Rejected,
        $actor,
        $note,
    ))->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->rejection_reason)->toBeNull()
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'missing' => [null],
    'empty' => [''],
    'whitespace' => ['   '],
]);

test('resolving without a meaningful note is refused without changing state or history', function (?string $note) {
    $actor = complaintTransitionUserWithRole('provost');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::InProgress]);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Resolved,
        $actor,
        $note,
    ))->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->resolution_note)->toBeNull()
        ->and($complaint->resolved_at)->toBeNull()
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'missing' => [null],
    'empty' => [''],
    'whitespace' => ['   '],
]);

test('a resolution stores the note on the complaint and in the audit history', function () {
    $actor = complaintTransitionUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::InProgress]);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Resolved,
        $actor,
        'Replaced the broken tap.',
    );

    expect($complaint->refresh()->resolution_note)->toBe('Replaced the broken tap.')
        ->and($complaint->statusLogs()->sole()->note)->toBe('Replaced the broken tap.');
});

test('an authorized but invalid graph transition is refused without changing state or history', function () {
    $actor = complaintTransitionUserWithRole('provost');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Resolved,
        $actor,
        'Skipped review.',
    ))->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('an authorized transition and its audit entry are rolled back together', function () {
    $actor = complaintTransitionUserWithRole('hall_office_staff');
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

function complaintTransitionUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintTransitionTutor(string $state = 'active'): User
{
    $user = complaintTransitionUserWithRole('house_tutor');

    if ($state !== 'without profile') {
        TutorProfile::factory()->for($user)->create(['is_active' => $state === 'active']);
    }

    return $user->fresh();
}
