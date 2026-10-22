<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintStatusLog;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('authorized staff can assign a complaint to an active house tutor', function (string $role) {
    $assigner = complaintAssignmentUserWithRole($role);
    $tutor = complaintAssignmentTutor();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Submitted]);

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->assignee_id)->toBe($tutor->id)
        ->and($complaint->assigned_at)->not->toBeNull();
})->with(['hall_office_staff', 'provost', 'super_admin']);

test('a complaint can be assigned to hall office staff', function () {
    $assigner = complaintAssignmentUserWithRole('provost');
    $officeStaff = complaintAssignmentUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->create();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $officeStaff->id])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->assignee_id)->toBe($officeStaff->id)
        ->and($complaint->assigned_at)->not->toBeNull();
});

test('assignment records the latest assignee and assignment timestamp', function () {
    $this->travelTo(now()->subHour());

    $assigner = complaintAssignmentUserWithRole('hall_office_staff');
    $firstTutor = complaintAssignmentTutor();
    $secondTutor = complaintAssignmentTutor();
    $complaint = Complaint::factory()->create();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $firstTutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    $firstAssignedAt = $complaint->refresh()->assigned_at;

    $this->travel(30)->minutes();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $secondTutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->assignee_id)->toBe($secondTutor->id)
        ->and($complaint->assigned_at?->greaterThan($firstAssignedAt))->toBeTrue();
});

test('assignment does not change complaint status or audit history', function () {
    $assigner = complaintAssignmentUserWithRole('super_admin');
    $tutor = complaintAssignmentTutor();
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::InProgress,
        'assignee_id' => null,
        'assigned_at' => null,
    ]);

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->refresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->statusLogs()->count())->toBe(0);
});

test('assignment changes tutor complaint visibility immediately', function () {
    $assigner = complaintAssignmentUserWithRole('provost');
    $tutor = complaintAssignmentTutor();
    $otherTutor = complaintAssignmentTutor();
    $complaint = Complaint::factory()->create([
        'assignee_id' => $otherTutor->id,
    ]);

    $this->actingAs($tutor)
        ->get(route('complaints.show', $complaint))
        ->assertForbidden();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    $this->actingAs($tutor)
        ->get(route('complaints.show', $complaint))
        ->assertOk();

    $this->actingAs($otherTutor)
        ->get(route('complaints.show', $complaint))
        ->assertForbidden();
});

test('users without assignment authority cannot assign a complaint', function (string $scope) {
    $complaint = Complaint::factory()->create();
    $assignee = complaintAssignmentTutor();
    $attacker = match ($scope) {
        'complaint owner' => complaintAssignmentStudent(),
        'house tutor' => complaintAssignmentTutor(),
        'club admin' => complaintAssignmentUserWithRole('club_admin'),
    };

    $this->actingAs($attacker)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $assignee->id])
        ->assertForbidden();

    expect($complaint->refresh()->assignee_id)->toBeNull()
        ->and($complaint->assigned_at)->toBeNull();
})->with(['complaint owner', 'house tutor', 'club admin']);

test('ineligible assignees are rejected', function (string $scope) {
    $assigner = complaintAssignmentUserWithRole('provost');
    $complaint = Complaint::factory()->create();
    $ineligible = match ($scope) {
        'student' => complaintAssignmentStudent(),
        'club admin' => complaintAssignmentUserWithRole('club_admin'),
        'inactive house tutor' => complaintAssignmentTutor(isActive: false),
        'house tutor without profile' => complaintAssignmentUserWithRole('house_tutor'),
        'inactive hall office staff' => complaintAssignmentUserWithRole('hall_office_staff', isActive: false),
    };

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $ineligible->id])
        ->assertSessionHasErrors('assignee_id');

    expect($complaint->refresh()->assignee_id)->toBeNull()
        ->and($complaint->assigned_at)->toBeNull();
})->with([
    'student',
    'club admin',
    'inactive house tutor',
    'house tutor without profile',
    'inactive hall office staff',
]);

test('an unknown assignee is rejected', function () {
    $assigner = complaintAssignmentUserWithRole('provost');
    $complaint = Complaint::factory()->create();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => 999999])
        ->assertSessionHasErrors('assignee_id');

    expect($complaint->refresh()->assignee_id)->toBeNull();
});

test('an assignee identifier is required', function () {
    $assigner = complaintAssignmentUserWithRole('provost');
    $complaint = Complaint::factory()->create();

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), [])
        ->assertSessionHasErrors('assignee_id');

    expect($complaint->refresh()->assignee_id)->toBeNull();
});

test('assignment preserves existing status log entries', function () {
    $assigner = complaintAssignmentUserWithRole('provost');
    $tutor = complaintAssignmentTutor();
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::UnderReview]);
    ComplaintStatusLog::factory()->for($complaint)->create([
        'actor_id' => $assigner->id,
        'from_status' => ComplaintStatus::Submitted,
        'status' => ComplaintStatus::UnderReview,
    ]);

    $this->actingAs($assigner)
        ->post(route('complaints.assign', $complaint), ['assignee_id' => $tutor->id])
        ->assertRedirect(route('complaints.show', $complaint));

    expect($complaint->statusLogs()->count())->toBe(1);
});

function complaintAssignmentUserWithRole(string $role, bool $isActive = true): User
{
    $user = User::factory()->create(['is_active' => $isActive]);
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintAssignmentStudent(): User
{
    $user = complaintAssignmentUserWithRole('student');
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintAssignmentTutor(bool $isActive = true): User
{
    $user = complaintAssignmentUserWithRole('house_tutor');
    TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);

    return $user->fresh();
}
