<?php

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Block;
use App\Models\Complaint;
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

test('a student lists only their own complaints', function () {
    $owner = complaintListStudent();
    $otherStudent = complaintListStudent();
    $ownComplaint = Complaint::factory()->for($owner->studentProfile)->create();
    Complaint::factory()->for($otherStudent->studentProfile)->create();

    $this->actingAs($owner)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/index', false)
            ->has('complaints.data', 1)
            ->where('complaints.total', 1)
            ->where('complaints.data.0.id', $ownComplaint->id)
        );
});

test('status and category filters only narrow a student list', function () {
    $owner = complaintListStudent();
    $otherStudent = complaintListStudent();

    $resolvedElectricity = Complaint::factory()->for($owner->studentProfile)->create([
        'category' => ComplaintCategory::Electricity,
        'status' => ComplaintStatus::Resolved,
    ]);
    Complaint::factory()->for($owner->studentProfile)->create([
        'category' => ComplaintCategory::Water,
        'status' => ComplaintStatus::Submitted,
    ]);
    Complaint::factory()->for($otherStudent->studentProfile)->create([
        'category' => ComplaintCategory::Electricity,
        'status' => ComplaintStatus::Submitted,
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.index', [
            'status' => ComplaintStatus::Resolved->value,
            'category' => ComplaintCategory::Electricity->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 1)
            ->where('complaints.total', 1)
            ->where('complaints.data.0.id', $resolvedElectricity->id)
            ->where('filters.status', ComplaintStatus::Resolved->value)
            ->where('filters.category', ComplaintCategory::Electricity->value)
        );
});

test('crafted filters cannot widen a student scope', function () {
    $owner = complaintListStudent();
    $otherStudent = complaintListStudent();

    Complaint::factory()->for($owner->studentProfile)->create([
        'category' => ComplaintCategory::Water,
        'status' => ComplaintStatus::Submitted,
    ]);
    Complaint::factory()->for($otherStudent->studentProfile)->create([
        'category' => ComplaintCategory::Electricity,
        'status' => ComplaintStatus::Submitted,
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.index', [
            'category' => ComplaintCategory::Electricity->value,
            'status' => ComplaintStatus::Submitted->value,
            'student_profile_id' => $otherStudent->studentProfile->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 0)
            ->where('complaints.total', 0)
        );
});

test('leadership lists every complaint within the selected filters', function (string $role) {
    $staff = complaintListUserWithRole($role);
    $first = Complaint::factory()->create(['category' => ComplaintCategory::Water]);
    $second = Complaint::factory()->create(['category' => ComplaintCategory::Internet]);

    $this->actingAs($staff)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 2)
            ->where('complaints.total', 2)
        );

    $this->actingAs($staff)
        ->get(route('complaints.index', ['category' => ComplaintCategory::Water->value]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 1)
            ->where('complaints.data.0.id', $first->id)
            ->where('complaints.data.0.id', fn (int $id) => $id !== $second->id)
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('a tutor lists only complaints whose room is in an assigned block', function () {
    $assignedBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = complaintListTutor([$assignedBlock]);
    $visible = complaintListComplaintInBlock($assignedBlock);
    $other = complaintListComplaintInBlock($otherBlock);
    $attached = Complaint::factory()->create([
        'location' => 'North Block corridor',
        'category' => ComplaintCategory::Security,
    ]);

    $this->actingAs($tutor)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 1)
            ->where('complaints.total', 1)
            ->where('complaints.data.0.id', $visible->id)
            ->where('complaints.data.0.id', fn (int $id) => ! in_array($id, [$other->id, $attached->id], true))
        );
});

test('crafted filters cannot widen a tutor block scope', function () {
    $assignedBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = complaintListTutor([$assignedBlock]);
    complaintListComplaintInBlock($assignedBlock, [
        'category' => ComplaintCategory::Water,
        'status' => ComplaintStatus::Submitted,
    ]);
    $other = complaintListComplaintInBlock($otherBlock, [
        'category' => ComplaintCategory::Electricity,
        'status' => ComplaintStatus::UnderReview,
    ]);

    $this->actingAs($tutor)
        ->get(route('complaints.index', [
            'category' => $other->category->value,
            'status' => $other->status->value,
            'block' => $otherBlock->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 0)
            ->where('complaints.total', 0)
        );
});

test('a tutor without active block assignments cannot list complaints', function (string $scope) {
    $tutor = match ($scope) {
        'no blocks' => complaintListTutor([]),
        'inactive' => complaintListTutor([Block::factory()->create()], isActive: false),
        default => complaintListUserWithRole('house_tutor'),
    };

    $this->actingAs($tutor)
        ->get(route('complaints.index'))
        ->assertForbidden();
})->with(['no blocks', 'inactive', 'no tutor profile']);

test('a user without a complaint role cannot list complaints', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('complaints.index'))
        ->assertForbidden();
});

test('a student complaint list is paginated', function () {
    $student = complaintListStudent();
    Complaint::factory()->for($student->studentProfile)->count(21)->create();

    $this->actingAs($student)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('complaints.data', 20)
            ->where('complaints.total', 21)
            ->where('complaints.per_page', 20)
            ->where('complaints.last_page', 2)
        );
});

test('a complaint list includes days open and overdue values', function () {
    $this->travelTo(now()->startOfDay());

    $student = complaintListStudent();
    $old = Complaint::factory()->for($student->studentProfile)->create([
        'status' => ComplaintStatus::Submitted,
        'created_at' => now()->subDays(10),
    ]);
    $resolved = Complaint::factory()->for($student->studentProfile)->create([
        'status' => ComplaintStatus::Resolved,
        'created_at' => now()->subDays(10),
    ]);
    $current = Complaint::factory()->for($student->studentProfile)->create([
        'status' => ComplaintStatus::Submitted,
        'created_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('complaints.data', function ($complaints) use ($old, $resolved, $current): bool {
                $items = collect($complaints)->keyBy('id');

                return $items[$old->id]['days_open'] === 10
                    && $items[$old->id]['is_overdue'] === true
                    && $items[$resolved->id]['is_overdue'] === false
                    && $items[$current->id]['days_open'] === 0
                    && $items[$current->id]['is_overdue'] === false;
            })
        );
});

test('invalid complaint list filters are rejected', function () {
    $student = complaintListStudent();

    $this->actingAs($student)
        ->get(route('complaints.index', [
            'status' => 'not-a-status',
            'category' => 'not-a-category',
        ]))
        ->assertSessionHasErrors(['status', 'category']);
});

function complaintListStudent(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'student']));
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintListUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}

/**
 * @param  array<int, Block>  $blocks
 */
function complaintListTutor(array $blocks, bool $isActive = true): User
{
    $user = complaintListUserWithRole('house_tutor');
    $tutor = TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);
    $tutor->blocks()->attach(collect($blocks)->pluck('id')->all());

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function complaintListComplaintInBlock(Block $block, array $attributes = []): Complaint
{
    $room = Room::factory()->for($block)->create();

    return Complaint::factory()->for($room)->create($attributes);
}
