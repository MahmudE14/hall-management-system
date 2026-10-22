<?php

use App\Enums\ComplaintCategory;
use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('an approved resident sees the current room and no location field on the submission page', function () {
    $user = complaintPageStudent(RegistrationStatus::Approved, ResidencyStatus::Resident);
    $room = Room::factory()->create(['room_no' => '214']);
    $seat = Seat::factory()->for($room)->create();
    SeatAllocation::factory()->for($user->studentProfile)->for($seat)->create(['active' => true]);

    $this->actingAs($user)
        ->get(route('complaints.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/create', false)
            ->where('currentRoom.room_no', '214')
            ->where('currentRoom.block', $room->block->name)
            ->where('requiresLocation', false)
            ->has('categories', count(ComplaintCategory::cases()))
        );
});

test('an approved attached student is asked for a location and has no current room', function () {
    $user = complaintPageStudent(RegistrationStatus::Approved, ResidencyStatus::Attached);

    $this->actingAs($user)
        ->get(route('complaints.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('complaints/create', false)
            ->where('currentRoom', null)
            ->where('requiresLocation', true)
            ->where('categories', array_column(ComplaintCategory::cases(), 'value'))
        );
});

test('students whose registration is not approved cannot open the submission page', function (RegistrationStatus $status) {
    $this->actingAs(complaintPageStudent($status, ResidencyStatus::Attached))
        ->get(route('complaints.create'))
        ->assertForbidden();
})->with([
    'pending' => RegistrationStatus::Pending,
    'rejected' => RegistrationStatus::Rejected,
]);

test('users without the student role cannot open the submission page', function (string $role) {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    $this->actingAs($user)
        ->get(route('complaints.create'))
        ->assertForbidden();
})->with(['super_admin', 'provost', 'hall_office_staff', 'house_tutor', 'club_admin']);

test('invalid submissions return field errors to the submission page', function () {
    $user = complaintPageStudent(RegistrationStatus::Approved, ResidencyStatus::Attached);

    $this->actingAs($user)
        ->from(route('complaints.create'))
        ->followingRedirects()
        ->post(route('complaints.store'), [
            'category' => '',
            'title' => '',
            'description' => '',
            'location' => '',
        ])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('errors.category', fn (string $message) => $message !== '')
            ->where('errors.title', fn (string $message) => $message !== '')
            ->where('errors.description', fn (string $message) => $message !== '')
            ->where('errors.location', fn (string $message) => $message !== '')
        );
});

test('a successful submission redirects to the dashboard with success feedback', function () {
    $user = complaintPageStudent(RegistrationStatus::Approved, ResidencyStatus::Resident);

    $response = $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Other->value,
            'title' => 'General issue',
            'description' => 'A general complaint about the hall.',
            'location' => 'Ignored for residents',
        ]);

    $response->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast');

    $flash = session('inertia.flash_data');

    expect($flash['toast']['type'])->toBe('success')
        ->and($flash['toast']['message'])->toContain('KJH-')
        ->and($flash['toast']['message'])->toContain('Complaint submitted');
});

function complaintPageStudent(
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
