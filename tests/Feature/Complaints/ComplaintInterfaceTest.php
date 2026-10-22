<?php

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Enums\RegistrationStatus;
use App\Models\Complaint;
use App\Models\ComplaintStatusLog;
use App\Models\Role;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('a complaint list exposes tracking, status, aging, location, assignee, and the private image route', function () {
    $this->travelTo(now()->startOfDay());

    $owner = complaintInterfaceStudent();
    $tutor = complaintInterfaceTutor();
    $room = Room::factory()->create(['room_no' => '312']);
    $complaint = Complaint::factory()->for($owner->studentProfile)->for($room)->create([
        'tracking_id' => 'KJH-2026-0042',
        'category' => ComplaintCategory::Electricity,
        'status' => ComplaintStatus::InProgress,
        'assignee_id' => $tutor->id,
        'image_path' => 'complaints/private-proof.png',
        'created_at' => now()->subDays(9),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/index', false)
            ->has('complaints.data', 1)
            ->where('complaints.data.0.tracking_id', 'KJH-2026-0042')
            ->where('complaints.data.0.category', ComplaintCategory::Electricity->value)
            ->where('complaints.data.0.status', ComplaintStatus::InProgress->value)
            ->where('complaints.data.0.room_no', '312')
            ->where('complaints.data.0.block', $room->block->name)
            ->where('complaints.data.0.days_open', 9)
            ->where('complaints.data.0.is_overdue', true)
            ->where('complaints.data.0.assignee.id', $tutor->id)
            ->where('complaints.data.0.assignee.name', $tutor->name)
            ->where('complaints.data.0.image_url', route('complaints.image', $complaint))
        );
});

test('the complaint list offers submission only to approved students', function (callable $makeUser, bool $expected) {
    $user = $makeUser();

    $this->actingAs($user)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can_create', $expected)
        );
})->with([
    'approved student' => [fn () => complaintInterfaceStudent(RegistrationStatus::Approved), true],
    'pending student' => [fn () => complaintInterfaceStudent(RegistrationStatus::Pending), false],
    'hall office staff' => [fn () => complaintInterfaceStaff('hall_office_staff'), false],
]);

test('a complaint detail exposes chronological status history with actors and notes', function () {
    $this->travelTo(now()->subHours(5));

    $owner = complaintInterfaceStudent();
    $staff = complaintInterfaceStaff('hall_office_staff');
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'status' => ComplaintStatus::InProgress,
    ]);

    $resolvedLater = ComplaintStatusLog::factory()->for($complaint)->create([
        'actor_id' => $staff->id,
        'from_status' => ComplaintStatus::UnderReview,
        'status' => ComplaintStatus::InProgress,
        'note' => 'Repair scheduled.',
        'created_at' => now()->addHours(3),
    ]);
    $earliest = ComplaintStatusLog::factory()->for($complaint)->create([
        'actor_id' => $staff->id,
        'from_status' => ComplaintStatus::Submitted,
        'status' => ComplaintStatus::UnderReview,
        'note' => 'Reviewing the report.',
        'created_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/show', false)
            ->where('complaint.status_history', function ($history) use ($earliest, $resolvedLater, $staff): bool {
                $history = collect($history)->values();

                return $history->pluck('id')->all() === [$earliest->id, $resolvedLater->id]
                    && $history[0]['from_status'] === ComplaintStatus::Submitted->value
                    && $history[0]['status'] === ComplaintStatus::UnderReview->value
                    && $history[0]['note'] === 'Reviewing the report.'
                    && $history[0]['actor']['name'] === $staff->name
                    && $history[1]['status'] === ComplaintStatus::InProgress->value
                    && $history[1]['note'] === 'Repair scheduled.'
                    && $history->every(fn (array $entry): bool => isset($entry['created_at']));
            })
        );
});

test('a complaint detail exposes the current assignee and tracking information', function () {
    $owner = complaintInterfaceStudent();
    $tutor = complaintInterfaceTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'tracking_id' => 'KJH-2026-0100',
        'assignee_id' => $tutor->id,
        'assigned_at' => now()->subHour(),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('complaint.tracking_id', 'KJH-2026-0100')
            ->where('complaint.assignee.id', $tutor->id)
            ->where('complaint.assignee.name', $tutor->name)
            ->where('complaint.assigned_at', fn (?string $value) => $value !== null)
        );
});

function complaintInterfaceStudent(
    RegistrationStatus $status = RegistrationStatus::Approved,
): User {
    $user = complaintInterfaceUserWithRole('student');
    StudentProfile::factory()->for($user)->create([
        'registration_status' => $status,
    ]);

    return $user->fresh();
}

function complaintInterfaceStaff(string $role): User
{
    return complaintInterfaceUserWithRole($role);
}

function complaintInterfaceUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintInterfaceTutor(): User
{
    $user = complaintInterfaceUserWithRole('house_tutor');
    TutorProfile::factory()->for($user)->create();

    return $user->fresh();
}
