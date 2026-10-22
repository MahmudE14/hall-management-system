<?php

use App\Enums\RegistrationStatus;
use App\Models\Block;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('a tutor sees only students currently allocated in an assigned block', function () {
    $assignedBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$assignedBlock]);
    $visible = tutorScopeStudentInBlock($assignedBlock);
    tutorScopeStudentInBlock($otherBlock);
    StudentProfile::factory()->create();

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.total', 1)
            ->where('students.data.0.id', $visible->id)
        );
});

test('a tutor assigned to multiple blocks sees students from every assigned block only', function () {
    $firstBlock = Block::factory()->create();
    $secondBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$firstBlock, $secondBlock]);
    $firstStudent = tutorScopeStudentInBlock($firstBlock);
    $secondStudent = tutorScopeStudentInBlock($secondBlock);
    tutorScopeStudentInBlock($otherBlock);

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 2)
            ->where('students.total', 2)
            ->where('students.data', fn ($students) => collect($students)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$firstStudent->id, $secondStudent->id])->sort()->values()->all())
        );
});

test('a tutor without block assignments cannot view the student directory', function () {
    $tutor = tutorScopeUserWithBlocks([]);

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertForbidden();
});

test('a tutor without a tutor profile cannot view the student directory', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'house_tutor']));

    $this->actingAs($user)
        ->get(route('students.index'))
        ->assertForbidden();
});

test('an inactive tutor cannot view scoped students', function () {
    $block = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$block], isActive: false);
    $student = tutorScopeStudentInBlock($block);

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertForbidden();

    $this->actingAs($tutor)
        ->get(route('students.show', $student))
        ->assertForbidden();
});

test('a tutor can view a profile in an assigned block', function () {
    $block = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$block]);
    $student = tutorScopeStudentInBlock($block);

    $this->actingAs($tutor)
        ->get(route('students.show', $student))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('student.id', $student->id)
        );
});

test('a tutor cannot view a profile outside every assigned block', function (string $scope) {
    $assignedBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$assignedBlock]);
    $student = $scope === 'other block'
        ? tutorScopeStudentInBlock(Block::factory()->create())
        : StudentProfile::factory()->create();

    $this->actingAs($tutor)
        ->get(route('students.show', $student))
        ->assertForbidden();
})->with(['other block', 'no current allocation']);

test('a tutor cannot bypass block scope with a crafted block filter', function () {
    $assignedBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$assignedBlock]);
    $assignedStudent = tutorScopeStudentInBlock($assignedBlock);
    $otherStudent = tutorScopeStudentInBlock($otherBlock);

    $this->actingAs($tutor)
        ->get(route('students.index', ['block' => $otherBlock->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 0)
            ->where('students.total', 0)
        );

    $this->actingAs($tutor)
        ->get(route('students.index', ['block' => $assignedBlock->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $assignedStudent->id)
            ->where('students.data.0.id', fn (int $id) => $id !== $otherStudent->id)
        );
});

test('historical allocations outside the current block do not grant tutor access', function () {
    $assignedBlock = Block::factory()->create();
    $currentBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$assignedBlock]);
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $pastRoom = Room::factory()->for($assignedBlock)->create();
    $pastSeat = Seat::factory()->for($pastRoom)->create();
    SeatAllocation::factory()->for($student)->for($pastSeat)->create([
        'allocated_at' => '2025-08-01',
        'active' => null,
        'vacated_at' => '2026-01-10',
        'vacancy_reason' => 'Room change',
    ]);

    $currentRoom = Room::factory()->for($currentBlock)->create();
    $currentSeat = Seat::factory()->for($currentRoom)->create();
    SeatAllocation::factory()->for($student)->for($currentSeat)->create([
        'allocated_at' => '2026-01-15',
    ]);

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 0)
            ->where('students.total', 0)
        );

    $this->actingAs($tutor)
        ->get(route('students.show', $student))
        ->assertForbidden();
});

test('a tutor directory only offers assigned blocks as filters', function () {
    $assignedBlock = Block::factory()->create();
    $otherBlock = Block::factory()->create();
    $tutor = tutorScopeUserWithBlocks([$assignedBlock]);

    $this->actingAs($tutor)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('blocks', 1)
            ->where('blocks.0.id', $assignedBlock->id)
            ->where('blocks.0.id', fn (int $id) => $id !== $otherBlock->id)
        );
});

/**
 * @param  array<int, Block>  $blocks
 */
function tutorScopeUserWithBlocks(array $blocks, bool $isActive = true): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'house_tutor']));

    $tutor = TutorProfile::factory()->for($user)->create(['is_active' => $isActive]);
    $tutor->blocks()->attach(collect($blocks)->pluck('id')->all());

    return $user;
}

function tutorScopeStudentInBlock(Block $block): StudentProfile
{
    $student = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $room = Room::factory()->for($block)->create();
    $seat = Seat::factory()->for($room)->create();

    SeatAllocation::factory()->for($student)->for($seat)->create([
        'allocated_at' => '2026-01-15',
    ]);

    return $student;
}
