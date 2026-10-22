<?php

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

test('authorized staff receive assignment controls with only eligible active assignees', function (string $role) {
    $assigner = complaintControlUserWithRole($role);
    $eligibleOfficeStaff = complaintControlUserWithRole('hall_office_staff');
    $eligibleTutor = complaintControlTutor();
    $ineligibleStudent = complaintControlStudent();
    $ineligibleClubAdmin = complaintControlUserWithRole('club_admin');
    $inactiveOfficeStaff = complaintControlUserWithRole('hall_office_staff', isActive: false);
    $inactiveTutor = complaintControlTutor(isActive: false);
    $roleOnlyTutor = complaintControlUserWithRole('house_tutor');
    $complaint = Complaint::factory()->create();

    $this->actingAs($assigner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_assign', true)
            ->where('can_comment', true)
            ->where('eligible_assignees', function ($assignees) use (
                $eligibleOfficeStaff,
                $eligibleTutor,
                $ineligibleStudent,
                $ineligibleClubAdmin,
                $inactiveOfficeStaff,
                $inactiveTutor,
                $roleOnlyTutor,
            ): bool {
                $assignees = collect($assignees)->keyBy('id');
                $ids = $assignees->keys()->all();

                return in_array($eligibleOfficeStaff->id, $ids, true)
                    && in_array($eligibleTutor->id, $ids, true)
                    && ! in_array($ineligibleStudent->id, $ids, true)
                    && ! in_array($ineligibleClubAdmin->id, $ids, true)
                    && ! in_array($inactiveOfficeStaff->id, $ids, true)
                    && ! in_array($inactiveTutor->id, $ids, true)
                    && ! in_array($roleOnlyTutor->id, $ids, true)
                    && $assignees[$eligibleOfficeStaff->id]['type'] === 'hall_office_staff'
                    && $assignees[$eligibleTutor->id]['type'] === 'house_tutor';
            })
        );
})->with(['hall_office_staff', 'provost', 'super_admin']);

test('complaint viewers without assignment authority receive no assignment controls', function (string $viewer) {
    $owner = complaintControlStudent();
    $tutor = complaintControlTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $tutor->id,
    ]);
    $user = $viewer === 'owner' ? $owner : $tutor;

    $this->actingAs($user)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_assign', false)
            ->where('can_comment', true)
            ->missing('eligible_assignees')
        );
})->with(['owner', 'assigned tutor']);

test('comment controls are exposed to the owner, current assignee, and staff', function (string $participant) {
    $owner = complaintControlStudent();
    $tutor = complaintControlTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $tutor->id,
    ]);
    $user = match ($participant) {
        'owner' => $owner,
        'current assignee' => $tutor,
        'hall office staff' => complaintControlUserWithRole('hall_office_staff'),
    };

    $this->actingAs($user)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_comment', true)
        );
})->with(['owner', 'current assignee', 'hall office staff']);

test('hidden assignment controls do not grant server access', function (string $scope) {
    $owner = complaintControlStudent();
    $tutor = complaintControlTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $tutor->id,
    ]);
    $attacker = $scope === 'owner' ? $owner : $tutor;

    $this->actingAs($attacker)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_assign', false)
            ->missing('eligible_assignees')
        );

    $this->actingAs($attacker)
        ->post(route('complaints.assign', $complaint), [
            'assignee_id' => $owner->id,
        ])
        ->assertForbidden();

    expect($complaint->refresh()->assignee_id)->toBe($tutor->id);
})->with(['owner', 'assigned tutor']);

function complaintControlStudent(): User
{
    $user = complaintControlUserWithRole('student');
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintControlUserWithRole(string $role, bool $isActive = true): User
{
    $user = User::factory()->create(['is_active' => $isActive]);
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintControlTutor(bool $isActive = true): User
{
    $user = complaintControlUserWithRole('house_tutor');
    TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);

    return $user->fresh();
}
