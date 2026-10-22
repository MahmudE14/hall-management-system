<?php

use App\Actions\Complaints\TransitionComplaint;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('the complaint owner can reopen a resolved complaint within the window', function () {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $this->travelTo($resolvedAt->copy()->addHours(167));

    $owner = complaintReopeningOwner();
    $complaint = complaintReopeningComplaint($owner, $resolvedAt);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::InProgress,
        $owner,
        'The leak returned.',
    );

    $log = $complaint->statusLogs()->sole();

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->resolved_at)->toBeNull()
        ->and($log->from_status)->toBe(ComplaintStatus::Resolved)
        ->and($log->status)->toBe(ComplaintStatus::InProgress)
        ->and($log->note)->toBe('The leak returned.');
});

test('the owner can reopen exactly at the inclusive 168-hour boundary', function (string $displayTimezone) {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $this->travelTo($resolvedAt->copy()->addHours(168)->setTimezone($displayTimezone));

    $owner = complaintReopeningOwner();
    $complaint = complaintReopeningComplaint($owner, $resolvedAt);

    app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::InProgress, $owner);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->resolved_at)->toBeNull();
})->with([
    'utc' => ['UTC'],
    'dhaka time' => ['Asia/Dhaka'],
    'new york time' => ['America/New_York'],
]);

test('the owner cannot reopen after the 168-hour window expires', function () {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $this->travelTo($resolvedAt->copy()->addHours(168)->addSecond());

    $owner = complaintReopeningOwner();
    $complaint = complaintReopeningComplaint($owner, $resolvedAt);

    expect(fn () => app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::InProgress, $owner))
        ->toThrow(ValidationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo($resolvedAt))->toBeTrue()
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('a non-owner student cannot reopen a resolved complaint', function () {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $this->travelTo($resolvedAt->copy()->addHour());

    $owner = complaintReopeningOwner();
    $otherStudent = complaintReopeningOwner();
    $complaint = complaintReopeningComplaint($owner, $resolvedAt);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::InProgress,
        $otherStudent,
    ))->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo($resolvedAt))->toBeTrue()
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('reopening preserves the earlier resolution event in the status history', function () {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $owner = complaintReopeningOwner();
    $complaint = Complaint::factory()->create([
        'student_profile_id' => $owner->studentProfile->id,
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => $resolvedAt,
        'resolution_note' => 'First resolution.',
    ]);
    $complaint->statusLogs()->create([
        'actor_id' => $owner->id,
        'from_status' => ComplaintStatus::InProgress,
        'status' => ComplaintStatus::Resolved,
        'note' => 'First resolution.',
    ])->forceFill(['created_at' => $resolvedAt])->save();

    $this->travelTo($resolvedAt->copy()->addHours(2));

    app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::InProgress, $owner, 'Reopened.');

    $logs = $complaint->statusLogs()->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->first()->status)->toBe(ComplaintStatus::Resolved)
        ->and($logs->first()->note)->toBe('First resolution.')
        ->and($logs->last()->status)->toBe(ComplaintStatus::InProgress);
});

test('resolving sets resolved_at and stores the resolution note', function () {
    $resolvedAt = Carbon::parse('2026-10-05 09:30:00', 'UTC');
    $this->travelTo($resolvedAt);

    $owner = complaintReopeningOwner();
    $actor = complaintReopeningUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create([
        'student_profile_id' => $owner->studentProfile->id,
        'status' => ComplaintStatus::InProgress,
    ]);

    app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::Resolved,
        $actor,
        'Leak repaired.',
    );

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo($resolvedAt))->toBeTrue()
        ->and($complaint->resolution_note)->toBe('Leak repaired.')
        ->and($complaint->statusLogs()->sole()->note)->toBe('Leak repaired.');
});

test('a reopened complaint that is resolved again uses the latest resolution time', function () {
    $firstResolution = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $owner = complaintReopeningOwner();
    $actor = complaintReopeningUserWithRole('provost');
    $complaint = Complaint::factory()->create([
        'student_profile_id' => $owner->studentProfile->id,
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => $firstResolution,
        'resolution_note' => 'First resolution.',
    ]);
    $complaint->statusLogs()->create([
        'actor_id' => $actor->id,
        'from_status' => ComplaintStatus::InProgress,
        'status' => ComplaintStatus::Resolved,
        'note' => 'First resolution.',
    ])->forceFill(['created_at' => $firstResolution])->save();

    $this->travelTo($firstResolution->copy()->addHour());
    app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::InProgress, $owner, 'Reopened.');

    $secondResolution = $firstResolution->copy()->addHours(3);
    $this->travelTo($secondResolution);
    app(TransitionComplaint::class)->handle($complaint, ComplaintStatus::Resolved, $actor, 'Repaired again.');

    $logs = $complaint->statusLogs()->get();

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo($secondResolution))->toBeTrue()
        ->and($complaint->resolution_note)->toBe('Repaired again.')
        ->and($logs)->toHaveCount(3)
        ->and($logs->first()->status)->toBe(ComplaintStatus::Resolved)
        ->and($logs->first()->note)->toBe('First resolution.')
        ->and($logs->get(1)->status)->toBe(ComplaintStatus::InProgress)
        ->and($logs->last()->status)->toBe(ComplaintStatus::Resolved)
        ->and($logs->last()->note)->toBe('Repaired again.');
});

test('an unauthorized reopening leaves the resolved state and history unchanged', function () {
    $resolvedAt = Carbon::parse('2026-10-01 12:00:00', 'UTC');
    $this->travelTo($resolvedAt->copy()->addHour());

    $owner = complaintReopeningOwner();
    $complaint = complaintReopeningComplaint($owner, $resolvedAt);
    $complaint->statusLogs()->create([
        'actor_id' => $owner->id,
        'from_status' => ComplaintStatus::InProgress,
        'status' => ComplaintStatus::Resolved,
        'note' => 'Resolved.',
    ]);

    expect(fn () => app(TransitionComplaint::class)->handle(
        $complaint,
        ComplaintStatus::InProgress,
        complaintReopeningUserWithRole('super_admin'),
    ))->toThrow(AuthorizationException::class);

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo($resolvedAt))->toBeTrue()
        ->and($complaint->statusLogs()->count())->toBe(1);
});

function complaintReopeningOwner(): User
{
    $user = complaintReopeningUserWithRole('student');
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintReopeningUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintReopeningComplaint(User $owner, Carbon $resolvedAt): Complaint
{
    return Complaint::factory()->create([
        'student_profile_id' => $owner->studentProfile->id,
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => $resolvedAt,
        'resolution_note' => 'Previously resolved.',
    ]);
}
