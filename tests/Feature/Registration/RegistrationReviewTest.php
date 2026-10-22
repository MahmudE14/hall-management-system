<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('registration reviewers can view pending registrations', function (string $role) {
    $pending = StudentProfile::factory()->create();
    StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs(reviewerWithRole($role))
        ->get(route('registrations.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('registrations/index')
            ->has('registrations.data', 1)
            ->where('registrations.data.0.id', $pending->id)
            ->where('registrations.data.0.user.email', $pending->user->email)
        );
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('other roles cannot view pending registrations', function () {
    $this->actingAs(reviewerWithRole('student'))
        ->get(route('registrations.index'))
        ->assertForbidden();
});

test('registration reviewers can approve a pending registration and history is retained', function (string $role) {
    $reviewer = reviewerWithRole($role);
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertRedirect(route('registrations.index'));

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Approved);
    expect($profile->registrationDecisions()->count())->toBe(1);

    $decision = $profile->registrationDecisions()->firstOrFail();
    expect($decision->status)->toBe(RegistrationStatus::Approved)
        ->and($decision->reason)->toBeNull()
        ->and($decision->actor_id)->toBe($reviewer->id)
        ->and($decision->created_at)->not->toBeNull();
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('registration reviewers can reject a pending registration with a reason', function (string $role) {
    $reviewer = reviewerWithRole($role);
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.reject', $profile), [
            'reason' => 'Incomplete registration details.',
        ])
        ->assertRedirect(route('registrations.index'));

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Rejected);

    $decision = $profile->registrationDecisions()->firstOrFail();
    expect($decision->status)->toBe(RegistrationStatus::Rejected)
        ->and($decision->reason)->toBe('Incomplete registration details.')
        ->and($decision->actor_id)->toBe($reviewer->id)
        ->and($decision->created_at)->not->toBeNull();
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('roles without registration decision access cannot approve registrations', function (string $role) {
    $profile = StudentProfile::factory()->create();

    $this->actingAs(reviewerWithRole($role))
        ->post(route('registrations.approve', $profile))
        ->assertForbidden();

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending)
        ->and($profile->registrationDecisions()->count())->toBe(0);
})->with(['student', 'club_admin', 'house_tutor']);

test('roles without registration decision access cannot reject registrations', function (string $role) {
    $profile = StudentProfile::factory()->create();

    $this->actingAs(reviewerWithRole($role))
        ->post(route('registrations.reject', $profile), [
            'reason' => 'Unauthorized rejection.',
        ])
        ->assertForbidden();

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending)
        ->and($profile->registrationDecisions()->count())->toBe(0);
})->with(['student', 'club_admin', 'house_tutor']);

test('rejection requires a reason', function () {
    $reviewer = reviewerWithRole('super_admin');
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.reject', $profile), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending);
    expect($profile->registrationDecisions()->count())->toBe(0);
});

test('a decided registration cannot be decided again', function () {
    $reviewer = reviewerWithRole('hall_office_staff');
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertStatus(422);

    $this->actingAs($reviewer)
        ->post(route('registrations.reject', $profile), [
            'reason' => 'Cannot reject a decided registration.',
        ])
        ->assertStatus(422);

    expect($profile->registrationDecisions()->count())->toBe(0);
});

function reviewerWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}

test('registration reviewers can reconsider a rejected registration without losing history', function (string $role) {
    $reviewer = reviewerWithRole($role);
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);
    $profile->user->roles()->attach(Role::query()->firstOrCreate(['name' => 'student']));

    $originalRejection = $profile->registrationDecisions()->create([
        'actor_id' => reviewerWithRole('provost')->id,
        'status' => RegistrationStatus::Rejected,
        'reason' => 'Original rejection reason.',
    ]);
    $originalActorId = $originalRejection->actor_id;
    $originalCreatedAt = $originalRejection->created_at;
    $rolesBefore = $profile->user->roles()->pluck('name')->all();

    $this->actingAs($reviewer)
        ->post(route('registrations.reconsider', $profile))
        ->assertRedirect(route('registrations.index', ['status' => 'rejected']));

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending);

    $decisions = $profile->registrationDecisions()->get()->sortBy('id')->values();

    expect($decisions)->toHaveCount(2)
        ->and($decisions[0]->is($originalRejection))->toBeTrue()
        ->and($decisions[0]->status)->toBe(RegistrationStatus::Rejected)
        ->and($decisions[0]->reason)->toBe('Original rejection reason.')
        ->and($decisions[0]->actor_id)->toBe($originalActorId)
        ->and($decisions[0]->created_at->equalTo($originalCreatedAt))->toBeTrue()
        ->and($decisions[1]->status)->toBe(RegistrationStatus::Pending)
        ->and($decisions[1]->actor_id)->toBe($reviewer->id)
        ->and($decisions[1]->reason)->toBeNull()
        ->and($decisions[1]->created_at)->not->toBeNull()
        ->and($profile->user->roles()->pluck('name')->all())->toBe($rolesBefore);
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('only pending registrations can be considered for approval or rejection', function () {
    $reviewer = reviewerWithRole('super_admin');
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertStatus(422);

    $this->actingAs($reviewer)
        ->post(route('registrations.reject', $profile), [
            'reason' => 'Cannot reject a rejected registration.',
        ])
        ->assertStatus(422);

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Rejected)
        ->and($profile->registrationDecisions()->count())->toBe(0);
});

test('roles without registration decision access cannot reconsider rejected registrations', function (string $role) {
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);

    $this->actingAs(reviewerWithRole($role))
        ->post(route('registrations.reconsider', $profile))
        ->assertForbidden();

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Rejected)
        ->and($profile->registrationDecisions()->count())->toBe(0);
})->with(['club_admin', 'house_tutor']);

test('students cannot reconsider their own rejected registration', function () {
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);

    $this->actingAs($profile->user)
        ->post(route('registrations.reconsider', $profile))
        ->assertForbidden();
});

test('only rejected registrations can be reconsidered', function () {
    $reviewer = reviewerWithRole('provost');
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.reconsider', $profile))
        ->assertStatus(422);
});
