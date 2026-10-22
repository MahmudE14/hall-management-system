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
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('the owner can view complaint detail with an authorized image route', function () {
    Storage::fake('local');

    $owner = complaintDetailStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'category' => ComplaintCategory::Water,
        'status' => ComplaintStatus::InProgress,
        'description' => 'The tap has been leaking for three days.',
        'image_path' => 'complaints/private-proof.png',
        'created_at' => now()->subDays(3),
    ]);
    Storage::disk('local')->put('complaints/private-proof.png', 'image-bytes');

    $this->actingAs($owner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/show', false)
            ->where('complaint.id', $complaint->id)
            ->where('complaint.tracking_id', $complaint->tracking_id)
            ->where('complaint.description', 'The tap has been leaking for three days.')
            ->where('complaint.category', ComplaintCategory::Water->value)
            ->where('complaint.status', ComplaintStatus::InProgress->value)
            ->where('complaint.days_open', 3)
            ->where('complaint.is_overdue', false)
            ->where('complaint.image_url', fn (string $url) => $url === route('complaints.image', $complaint)
                && ! str_contains($url, 'complaints/private-proof.png')
                && ! str_contains($url, '/storage/'))
        );
});

test('another student cannot view a complaint or its image', function () {
    Storage::fake('local');

    $owner = complaintDetailStudent();
    $otherStudent = complaintDetailStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'image_path' => 'complaints/private-proof.png',
    ]);
    Storage::disk('local')->put('complaints/private-proof.png', 'image-bytes');

    $this->actingAs($otherStudent)
        ->get(route('complaints.show', $complaint))
        ->assertForbidden();

    $this->actingAs($otherStudent)
        ->get(route('complaints.image', $complaint))
        ->assertForbidden();
});

test('leadership can view and download any complaint image', function (string $role) {
    Storage::fake('local');

    $staff = complaintDetailUserWithRole($role);
    $complaint = Complaint::factory()->create([
        'image_path' => 'complaints/private-proof.png',
    ]);
    Storage::disk('local')->put('complaints/private-proof.png', 'image-bytes');

    $this->actingAs($staff)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('complaint.id', $complaint->id)
        );

    $response = $this->actingAs($staff)
        ->get(route('complaints.image', $complaint))
        ->assertOk();

    expect($response->streamedContent())->toBe('image-bytes');
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('a tutor can view and download images only for complaints assigned to them', function () {
    Storage::fake('local');

    $assignedBlock = Block::factory()->create();
    $tutor = complaintDetailTutor([$assignedBlock]);
    $otherTutor = complaintDetailTutor([$assignedBlock]);
    $visible = Complaint::factory()->create([
        'assignee_id' => $tutor->id,
        'location' => 'North Block corridor',
        'image_path' => 'complaints/visible.png',
    ]);
    $unassignedInAssignedBlock = complaintDetailComplaintInBlock($assignedBlock, [
        'image_path' => 'complaints/unassigned.png',
    ]);
    $assignedToAnotherTutor = complaintDetailComplaintInBlock($assignedBlock, [
        'assignee_id' => $otherTutor->id,
        'image_path' => 'complaints/other.png',
    ]);
    Storage::disk('local')->put('complaints/visible.png', 'visible-bytes');
    Storage::disk('local')->put('complaints/unassigned.png', 'unassigned-bytes');
    Storage::disk('local')->put('complaints/other.png', 'other-bytes');

    $this->actingAs($tutor)
        ->get(route('complaints.show', $visible))
        ->assertOk();

    $response = $this->actingAs($tutor)
        ->get(route('complaints.image', $visible))
        ->assertOk();

    expect($response->streamedContent())->toBe('visible-bytes');

    foreach ([$unassignedInAssignedBlock, $assignedToAnotherTutor] as $forbidden) {
        $this->actingAs($tutor)
            ->get(route('complaints.show', $forbidden))
            ->assertForbidden();

        $this->actingAs($tutor)
            ->get(route('complaints.image', $forbidden))
            ->assertForbidden();
    }
});

test('a tutor cannot view an unassigned roomless complaint', function () {
    $tutor = complaintDetailTutor([Block::factory()->create()]);
    $attached = Complaint::factory()->create([
        'location' => 'North Block corridor',
        'image_path' => 'complaints/attached.png',
    ]);

    $this->actingAs($tutor)
        ->get(route('complaints.show', $attached))
        ->assertForbidden();

    $this->actingAs($tutor)
        ->get(route('complaints.image', $attached))
        ->assertForbidden();
});

test('an inactive or role-only tutor cannot view an assigned complaint', function () {
    $inactive = complaintDetailTutor([], isActive: false);
    $roleOnly = complaintDetailUserWithRole('house_tutor');
    $inactiveComplaint = Complaint::factory()->create(['assignee_id' => $inactive->id]);
    $roleOnlyComplaint = Complaint::factory()->create(['assignee_id' => $roleOnly->id]);

    $this->actingAs($inactive)
        ->get(route('complaints.show', $inactiveComplaint))
        ->assertForbidden();

    $this->actingAs($roleOnly)
        ->get(route('complaints.show', $roleOnlyComplaint))
        ->assertForbidden();
});

test('a missing authorized image returns not found', function () {
    Storage::fake('local');

    $owner = complaintDetailStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'image_path' => 'complaints/missing.png',
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.image', $complaint))
        ->assertNotFound();
});

test('a complaint without an image returns not found', function () {
    $owner = complaintDetailStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create();

    $this->actingAs($owner)
        ->get(route('complaints.image', $complaint))
        ->assertNotFound();
});

function complaintDetailStudent(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'student']));
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintDetailUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}

/**
 * @param  array<int, Block>  $blocks
 */
function complaintDetailTutor(array $blocks, bool $isActive = true): User
{
    $user = complaintDetailUserWithRole('house_tutor');
    $tutor = TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);
    $tutor->blocks()->attach(collect($blocks)->pluck('id')->all());

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function complaintDetailComplaintInBlock(Block $block, array $attributes = []): Complaint
{
    $room = Room::factory()->for($block)->create();

    return Complaint::factory()->for($room)->create($attributes);
}
