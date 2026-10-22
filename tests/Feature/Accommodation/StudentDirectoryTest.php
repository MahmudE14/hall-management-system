<?php

use App\Enums\RegistrationStatus;
use App\Models\Block;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('staff can view the paginated student directory with all records', function (string $role) {
    $viewer = studentRecordUserWithRole($role);
    $first = StudentProfile::factory()->create([
        'registration_number' => '2023000001',
        'department' => 'English',
        'session' => '2023-24',
    ]);
    StudentProfile::factory()->create([
        'registration_number' => '2023000002',
        'department' => 'History',
        'session' => '2022-23',
    ]);

    $this->actingAs($viewer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('students/index', false)
            ->has('students.data', 2)
            ->where('students.total', 2)
            ->where('students.per_page', 20)
            ->where('students.data', fn ($students) => collect($students)->contains(
                fn ($student) => $student['id'] === $first->id
                    && $student['registration_number'] === '2023000001'
                    && $student['name'] === $first->user->name
                    && $student['department'] === 'English'
                    && $student['session'] === '2023-24'
                    && $student['registration_status'] === RegistrationStatus::Pending->value
                    && $student['current_room'] === null
                    && $student['block'] === null,
            ))
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('the directory searches by student name or registration number', function (string $search) {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $target = StudentProfile::factory()->create(['registration_number' => '2023000077']);
    $target->user->update(['name' => 'Searchable Student']);
    StudentProfile::factory()->create(['registration_number' => '2023000088']);

    $this->actingAs($viewer)
        ->get(route('students.index', ['search' => $search]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $target->id)
        );
})->with([
    'name' => 'Searchable Student',
    'registration number' => '2023000077',
]);

test('the directory combines department, session, and status filters', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $match = StudentProfile::factory()->create([
        'department' => 'English',
        'session' => '2023-24',
        'registration_status' => RegistrationStatus::Approved,
    ]);
    StudentProfile::factory()->create([
        'department' => 'History',
        'session' => '2023-24',
        'registration_status' => RegistrationStatus::Approved,
    ]);
    StudentProfile::factory()->create([
        'department' => 'English',
        'session' => '2022-23',
        'registration_status' => RegistrationStatus::Approved,
    ]);
    StudentProfile::factory()->create([
        'department' => 'English',
        'session' => '2023-24',
        'registration_status' => RegistrationStatus::Pending,
    ]);

    $this->actingAs($viewer)
        ->get(route('students.index', [
            'department' => 'English',
            'session' => '2023-24',
            'status' => RegistrationStatus::Approved->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $match->id)
            ->where('filters.department', 'English')
            ->where('filters.session', '2023-24')
            ->where('filters.status', RegistrationStatus::Approved->value)
        );
});

test('the directory filters students by their current active block', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $blockA = Block::factory()->create();
    $blockB = Block::factory()->create();
    $studentA = StudentProfile::factory()->create();
    $studentB = StudentProfile::factory()->create();
    studentDirectoryAllocation($studentA, $blockA);
    studentDirectoryAllocation($studentB, $blockB);

    $this->actingAs($viewer)
        ->get(route('students.index', ['block' => $blockA->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.total', 1)
            ->where('students.data.0.id', $studentA->id)
            ->where('filters.block', $blockA->id)
            ->has('blocks', 2)
        );
});

test('historical allocations do not match the current block filter', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $block = Block::factory()->create();
    $student = StudentProfile::factory()->create();
    $allocation = studentDirectoryAllocation($student, $block);
    $allocation->update([
        'active' => null,
        'vacated_at' => '2026-03-01',
        'vacancy_reason' => 'Moved out',
    ]);

    $this->actingAs($viewer)
        ->get(route('students.index', ['block' => $block->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 0)
            ->where('students.total', 0)
        );
});

test('the directory combines the block filter with registration status', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $block = Block::factory()->create();
    $match = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);
    $pending = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Pending,
    ]);
    studentDirectoryAllocation($match, $block);
    studentDirectoryAllocation($pending, $block);

    $this->actingAs($viewer)
        ->get(route('students.index', [
            'block' => $block->id,
            'status' => RegistrationStatus::Approved->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $match->id)
            ->where('filters.block', $block->id)
            ->where('filters.status', RegistrationStatus::Approved->value)
        );
});

test('the directory shows the current room and block from the active allocation', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    $block = Block::factory()->create();
    $resident = StudentProfile::factory()->create();
    $allocation = studentDirectoryAllocation($resident, $block);
    $attached = StudentProfile::factory()->create();

    $this->actingAs($viewer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 2)
            ->where('students.data', function ($students) use ($resident, $attached, $allocation) {
                $residentRow = collect($students)->firstWhere('id', $resident->id);
                $attachedRow = collect($students)->firstWhere('id', $attached->id);

                return $residentRow['current_room']['room_no'] === $allocation->seat->room->room_no
                    && $residentRow['block']['id'] === $allocation->seat->room->block_id
                    && $attachedRow['current_room'] === null
                    && $attachedRow['block'] === null;
            })
        );
});

test('students only match their own active allocation when filtering by block', function () {
    $viewer = studentRecordUserWithRole('student');
    $block = Block::factory()->create();
    studentDirectoryAllocation($viewer->studentProfile, $block);
    studentDirectoryAllocation(StudentProfile::factory()->create(), $block);

    $this->actingAs($viewer)
        ->get(route('students.index', ['block' => $block->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $viewer->studentProfile->id)
        );
});

test('the directory is paginated', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    StudentProfile::factory()->count(25)->create();

    $this->actingAs($viewer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 20)
            ->where('students.total', 25)
            ->where('students.per_page', 20)
        );
});

test('students and club admins only see their own student record', function (string $role) {
    $viewer = studentRecordUserWithRole($role);
    $own = $viewer->studentProfile;
    StudentProfile::factory()->create();

    $this->actingAs($viewer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $own->id)
            ->where('students.total', 1)
        );
})->with(['student', 'club_admin']);

test('house tutors cannot view the student directory before block assignments exist', function () {
    $this->actingAs(studentRecordUserWithRole('house_tutor'))
        ->get(route('students.index'))
        ->assertForbidden();
});

test('the directory keeps its eager-loaded query count bounded', function () {
    $viewer = studentRecordUserWithRole('hall_office_staff');
    StudentProfile::factory()->create();

    DB::enableQueryLog();
    $this->actingAs(User::query()->findOrFail($viewer->id))
        ->get(route('students.index'))
        ->assertOk();
    $singleRecordQueries = count(DB::getQueryLog());

    StudentProfile::factory()->count(24)->create();
    DB::flushQueryLog();

    $this->actingAs(User::query()->findOrFail($viewer->id))
        ->get(route('students.index'))
        ->assertOk();
    $fullPageQueries = count(DB::getQueryLog());

    expect($fullPageQueries)->toBe($singleRecordQueries);
});

function studentDirectoryAllocation(StudentProfile $student, Block $block): SeatAllocation
{
    $room = Room::factory()->for($block)->create();
    $seat = Seat::factory()->for($room)->create();

    return SeatAllocation::factory()->for($student)->for($seat)->create([
        'allocated_at' => '2026-01-15',
    ]);
}

function studentRecordUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    if (in_array($role, ['student', 'club_admin'], true)) {
        StudentProfile::factory()->for($user)->create([
            'registration_status' => RegistrationStatus::Approved,
        ]);
    }

    return $user;
}
