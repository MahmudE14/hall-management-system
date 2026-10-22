<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
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
            ->where('block_filter_available', false)
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
