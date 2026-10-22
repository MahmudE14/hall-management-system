<?php

use App\Models\Complaint;
use App\Models\ComplaintComment;
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

test('authorized participants can add a complaint comment', function (string $participant) {
    $owner = complaintCommentStudent();
    $tutor = complaintCommentTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $tutor->id,
    ]);

    $commenter = match ($participant) {
        'owner' => $owner,
        'current assignee' => $tutor,
        'hall office staff' => complaintCommentUserWithRole('hall_office_staff'),
        'provost' => complaintCommentUserWithRole('provost'),
        'super admin' => complaintCommentUserWithRole('super_admin'),
    };

    $this->actingAs($commenter)
        ->post(route('complaints.comments.store', $complaint), ['body' => 'We are looking into this.'])
        ->assertRedirect(route('complaints.show', $complaint));

    $comment = $complaint->comments()->sole();

    expect($comment->author_id)->toBe($commenter->id)
        ->and($comment->body)->toBe('We are looking into this.')
        ->and($comment->created_at)->not->toBeNull();
})->with(['owner', 'current assignee', 'hall office staff', 'provost', 'super admin']);

test('an unauthorized user cannot add a complaint comment', function (string $scope) {
    $owner = complaintCommentStudent();
    $assignedTutor = complaintCommentTutor();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $assignedTutor->id,
    ]);
    $attacker = match ($scope) {
        'another student' => complaintCommentStudent(),
        'unassigned tutor' => complaintCommentTutor(),
        'club admin' => complaintCommentUserWithRole('club_admin'),
        'inactive assigned tutor' => complaintCommentTutor(isActive: false),
    };

    if ($scope === 'inactive assigned tutor') {
        $complaint->update(['assignee_id' => $attacker->id]);
    }

    $this->actingAs($attacker)
        ->post(route('complaints.comments.store', $complaint), ['body' => 'Crafted cross-record comment.'])
        ->assertForbidden();

    expect($complaint->comments()->count())->toBe(0);
})->with(['another student', 'unassigned tutor', 'club admin', 'inactive assigned tutor']);

test('a comment body is required', function () {
    $owner = complaintCommentStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create();

    $this->actingAs($owner)
        ->post(route('complaints.comments.store', $complaint), [])
        ->assertSessionHasErrors('body');

    expect($complaint->comments()->count())->toBe(0);
});

test('a comment body must be text', function () {
    $owner = complaintCommentStudent();
    $complaint = Complaint::factory()->for($owner->studentProfile)->create();

    $this->actingAs($owner)
        ->post(route('complaints.comments.store', $complaint), ['body' => ['not', 'text']])
        ->assertSessionHasErrors('body');

    expect($complaint->comments()->count())->toBe(0);
});

test('complaint detail returns comments in chronological order with author and timestamp', function () {
    $this->travelTo(now()->subHours(3));

    $owner = complaintCommentStudent();
    $staff = complaintCommentUserWithRole('hall_office_staff');
    $complaint = Complaint::factory()->for($owner->studentProfile)->create();

    $latest = ComplaintComment::factory()->for($complaint)->for($owner, 'author')->create([
        'body' => 'Latest update.',
        'created_at' => now()->addHours(2),
    ]);
    $earliest = ComplaintComment::factory()->for($complaint)->for($owner, 'author')->create([
        'body' => 'First report.',
        'created_at' => now(),
    ]);
    $middle = ComplaintComment::factory()->for($complaint)->for($staff, 'author')->create([
        'body' => 'We are investigating.',
        'created_at' => now()->addHour(),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/show', false)
            ->where('complaint.comments', function ($comments) use ($earliest, $middle, $latest, $owner, $staff): bool {
                $comments = collect($comments)->values()->all();

                return collect($comments)->pluck('id')->all() === [
                    $earliest->id,
                    $middle->id,
                    $latest->id,
                ]
                    && $comments[0]['body'] === 'First report.'
                    && $comments[0]['author']['name'] === $owner->name
                    && $comments[1]['author']['name'] === $staff->name
                    && $comments[2]['body'] === 'Latest update.'
                    && collect($comments)->every(fn (array $comment): bool => isset($comment['created_at']));
            })
        );
});

test('comments are stored only on the addressed complaint', function () {
    $owner = complaintCommentStudent();
    $assignedTutor = complaintCommentTutor();
    $addressed = Complaint::factory()->for($owner->studentProfile)->create([
        'assignee_id' => $assignedTutor->id,
    ]);
    $otherComplaint = Complaint::factory()->create();

    $this->actingAs($owner)
        ->post(route('complaints.comments.store', $addressed), [
            'body' => 'Addressed complaint only.',
            'complaint_id' => $otherComplaint->id,
        ])
        ->assertRedirect(route('complaints.show', $addressed));

    expect($addressed->comments()->count())->toBe(1)
        ->and($otherComplaint->comments()->count())->toBe(0);
});

function complaintCommentStudent(): User
{
    $user = complaintCommentUserWithRole('student');
    StudentProfile::factory()->for($user)->create();

    return $user->fresh();
}

function complaintCommentUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user->fresh();
}

function complaintCommentTutor(bool $isActive = true): User
{
    $user = complaintCommentUserWithRole('house_tutor');
    TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);

    return $user->fresh();
}
