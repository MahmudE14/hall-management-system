<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('staff available transitions follow the state graph and closure authority', function (
    string $role,
    ComplaintStatus $status,
    array $expected,
) {
    $staff = complaintTransitionControlsUserWithRole($role);
    $complaint = Complaint::factory()->create(['status' => $status]);

    $this->actingAs($staff)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('available_transitions', $expected)
        );
})->with([
    'office staff submitted' => ['hall_office_staff', ComplaintStatus::Submitted, ['under_review', 'rejected']],
    'provost under review' => ['provost', ComplaintStatus::UnderReview, ['in_progress', 'rejected']],
    'super admin in progress' => ['super_admin', ComplaintStatus::InProgress, ['resolved']],
    'office staff resolved' => ['hall_office_staff', ComplaintStatus::Resolved, ['closed']],
    'provost resolved' => ['provost', ComplaintStatus::Resolved, ['closed']],
    'super admin resolved' => ['super_admin', ComplaintStatus::Resolved, []],
]);

test('assigned tutors see only tutor-permitted transitions', function (
    ComplaintStatus $status,
    array $expected,
) {
    $tutor = complaintTransitionControlsTutor();
    $complaint = Complaint::factory()->create([
        'status' => $status,
        'assignee_id' => $tutor->id,
        'resolved_at' => $status === ComplaintStatus::Resolved ? now() : null,
    ]);

    $this->actingAs($tutor)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('available_transitions', $expected)
        );
})->with([
    'submitted' => [ComplaintStatus::Submitted, ['under_review']],
    'under review' => [ComplaintStatus::UnderReview, ['in_progress']],
    'in progress' => [ComplaintStatus::InProgress, ['resolved']],
    'resolved' => [ComplaintStatus::Resolved, []],
]);

test('the owner sees reopening only inside the inclusive UTC 168-hour window', function (
    int $hours,
    int $seconds,
    bool $visible,
) {
    $this->travelTo(now()->utc()->startOfSecond());

    $owner = complaintTransitionControlsStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => now()->utc()->subHours($hours)->subSeconds($seconds),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('available_transitions', $visible ? ['in_progress'] : [])
        );
})->with([
    'inside the window' => [167, 59, true],
    'exact boundary' => [168, 0, true],
    'one second after' => [168, 1, false],
]);

test('an authorized staff transition succeeds through the endpoint', function () {
    $staff = complaintTransitionControlsUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    $this->actingAs($staff)
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => ComplaintStatus::UnderReview->value,
        ])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::UnderReview)
        ->and($complaint->statusLogs()->count())->toBe(1)
        ->and($complaint->statusLogs()->sole()->actor_id)->toBe($staff->id);
});

test('an invalid transition returns visible validation errors and writes nothing', function () {
    $staff = complaintTransitionControlsUserWithRole('provost');
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    $this->actingAs($staff)
        ->from(route('complaints.show', $complaint))
        ->followingRedirects()
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => ComplaintStatus::InProgress->value,
        ])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('errors.status', fn (string $message) => $message !== '')
        );

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('rejection and resolution notes are required by the endpoint', function (
    ComplaintStatus $fromStatus,
    ComplaintStatus $toStatus,
) {
    $staff = complaintTransitionControlsUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create(['status' => $fromStatus]);

    $this->actingAs($staff)
        ->from(route('complaints.show', $complaint))
        ->followingRedirects()
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => $toStatus->value,
            'note' => '   ',
        ])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('errors.note', fn (string $message) => $message !== '')
        );

    expect($complaint->refresh()->status)->toBe($fromStatus)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'rejection' => [ComplaintStatus::Submitted, ComplaintStatus::Rejected],
    'resolution' => [ComplaintStatus::InProgress, ComplaintStatus::Resolved],
]);

test('unauthorized transitions remain denied server-side', function (string $scope) {
    $owner = complaintTransitionControlsStudent();
    $tutor = complaintTransitionControlsTutor();
    $status = $scope === 'super admin closure'
        ? ComplaintStatus::Resolved
        : ComplaintStatus::Submitted;
    $target = match ($scope) {
        'owner forward transition' => ComplaintStatus::UnderReview,
        'assigned tutor rejection' => ComplaintStatus::Rejected,
        'super admin closure' => ComplaintStatus::Closed,
        'unrelated student reopening' => ComplaintStatus::InProgress,
    };
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'status' => $scope === 'unrelated student reopening'
            ? ComplaintStatus::Resolved
            : $status,
        'assignee_id' => $tutor->id,
        'resolved_at' => $scope === 'unrelated student reopening' ? now() : null,
    ]);
    $actor = match ($scope) {
        'owner forward transition', 'unrelated student reopening' => $scope === 'unrelated student reopening'
            ? complaintTransitionControlsStudent()
            : $owner,
        'assigned tutor rejection' => $tutor,
        'super admin closure' => complaintTransitionControlsUserWithRole('super_admin'),
    };
    $originalStatus = $complaint->status;

    $this->actingAs($actor)
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => $target->value,
            'note' => 'Attempted unauthorized transition.',
        ])
        ->assertForbidden();

    expect($complaint->refresh()->status)->toBe($originalStatus)
        ->and($complaint->statusLogs()->count())->toBe(0);
})->with([
    'owner forward transition',
    'assigned tutor rejection',
    'super admin closure',
    'unrelated student reopening',
]);

test('the owner can reopen inside the window and is refused after it', function () {
    $this->travelTo(now()->utc()->startOfSecond());

    $owner = complaintTransitionControlsStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => now()->utc()->subHours(168),
    ]);

    $this->actingAs($owner)
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => ComplaintStatus::InProgress->value,
        ])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->resolved_at)->toBeNull()
        ->and($complaint->statusLogs()->count())->toBe(1);

    $complaint->update([
        'status' => ComplaintStatus::Resolved,
        'resolved_at' => now()->utc()->subHours(168)->subSecond(),
    ]);

    $this->actingAs($owner)
        ->from(route('complaints.show', $complaint))
        ->followingRedirects()
        ->post(route('complaints.transitions.store', $complaint), [
            'status' => ComplaintStatus::InProgress->value,
        ])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('errors.status', fn (string $message) => $message !== '')
        );

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->statusLogs()->count())->toBe(1);
});

function complaintTransitionControlsStudent(): User
{
    $user = complaintTransitionControlsUserWithRole('student');
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintTransitionControlsUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintTransitionControlsTutor(): User
{
    $user = complaintTransitionControlsUserWithRole('house_tutor');
    TutorProfile::factory()->for($user)->create();

    return $user->fresh();
}
