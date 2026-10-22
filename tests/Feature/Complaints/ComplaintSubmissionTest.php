<?php

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Complaint;
use App\Models\Role;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('an approved resident complaint is created with the current room filled in', function () {
    $user = complaintStudent(RegistrationStatus::Approved, ResidencyStatus::Resident);
    $student = $user->studentProfile;
    $seat = Seat::factory()->create();
    SeatAllocation::factory()->for($student)->for($seat)->create(['active' => true]);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Water->value,
            'title' => 'Leaking tap',
            'description' => 'The tap in the bathroom has been leaking since yesterday.',
            'location' => 'This should be ignored for residents.',
        ])
        ->assertRedirect(route('dashboard'));

    $complaint = Complaint::query()->sole();

    expect($complaint->student_profile_id)->toBe($student->id)
        ->and($complaint->room_id)->toBe($seat->room_id)
        ->and($complaint->location)->toBeNull()
        ->and($complaint->category)->toBe(ComplaintCategory::Water)
        ->and($complaint->title)->toBe('Leaking tap')
        ->and($complaint->status)->toBe(ComplaintStatus::Submitted)
        ->and($complaint->tracking_id)->toMatch('/^KJH-\d{4}-\d{4}$/');
});

test('an approved attached student complaint stores the supplied location', function () {
    $user = complaintStudent(RegistrationStatus::Approved, ResidencyStatus::Attached);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Electricity->value,
            'title' => 'Corridor light is out',
            'description' => 'The corridor light on the second floor is not working.',
            'location' => 'North Block, second floor corridor',
        ])
        ->assertRedirect(route('dashboard'));

    $complaint = Complaint::query()->sole();

    expect($complaint->student_profile_id)->toBe($user->studentProfile->id)
        ->and($complaint->room_id)->toBeNull()
        ->and($complaint->location)->toBe('North Block, second floor corridor')
        ->and($complaint->category)->toBe(ComplaintCategory::Electricity);
});

test('an approved attached student must provide a location', function () {
    $user = complaintStudent(RegistrationStatus::Approved, ResidencyStatus::Attached);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Security->value,
            'title' => 'Broken gate lock',
            'description' => 'The side gate lock is broken.',
            'location' => '',
        ])
        ->assertSessionHasErrors('location');

    expect(Complaint::query()->count())->toBe(0);
});

test('complaint submission validates required fields', function () {
    $user = complaintStudent(RegistrationStatus::Approved, ResidencyStatus::Resident);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => 'unsupported',
            'title' => '',
            'description' => '',
        ])
        ->assertSessionHasErrors(['category', 'title', 'description']);

    expect(Complaint::query()->count())->toBe(0);
});

test('students whose registration is not approved cannot submit complaints', function (RegistrationStatus $status) {
    $user = complaintStudent($status, ResidencyStatus::Attached);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Cleanliness->value,
            'title' => 'Dirty washroom',
            'description' => 'The shared washroom has not been cleaned.',
            'location' => 'Attached student location',
        ])
        ->assertForbidden();

    expect(Complaint::query()->count())->toBe(0);
})->with([
    'pending' => RegistrationStatus::Pending,
    'rejected' => RegistrationStatus::Rejected,
]);

test('users without the student role cannot submit complaints', function (string $role) {
    $user = complaintUserWithRole($role);

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Internet->value,
            'title' => 'No internet',
            'description' => 'The hall internet connection is unavailable.',
            'location' => 'Somewhere',
        ])
        ->assertForbidden();

    expect(Complaint::query()->count())->toBe(0);
})->with(['super_admin', 'provost', 'hall_office_staff', 'house_tutor', 'club_admin']);

function complaintStudent(
    RegistrationStatus $status,
    ResidencyStatus $residency,
): User {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'student']));
    StudentProfile::factory()->for($user)->create([
        'registration_status' => $status,
        'residency_status' => $residency,
    ]);

    return $user->fresh();
}

function complaintUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}
