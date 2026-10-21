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

test('a reviewer can approve a pending registration and history is retained', function () {
    $reviewer = reviewerWithRole('hall_office_staff');
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertRedirect(route('registrations.index'));

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Approved);
    expect($profile->registrationDecisions()->count())->toBe(1);

    $decision = $profile->registrationDecisions()->firstOrFail();
    expect($decision->status)->toBe(RegistrationStatus::Approved)
        ->and($decision->reason)->toBeNull()
        ->and($decision->actor_id)->toBe($reviewer->id);
});

test('a reviewer can reject a pending registration with a reason', function () {
    $reviewer = reviewerWithRole('provost');
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
        ->and($decision->actor_id)->toBe($reviewer->id);
});

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

    expect($profile->registrationDecisions()->count())->toBe(0);
});

function reviewerWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => $role]));

    return $user;
}

test('a reviewer can reconsider a rejected registration without losing history', function () {
    $reviewer = reviewerWithRole('hall_office_staff');
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);
    $originalRejection = $profile->registrationDecisions()->create([
        'actor_id' => reviewerWithRole('provost')->id,
        'status' => RegistrationStatus::Rejected,
        'reason' => 'Original rejection reason.',
    ]);

    $this->actingAs($reviewer)
        ->post(route('registrations.reconsider', $profile))
        ->assertRedirect(route('registrations.index', ['status' => 'rejected']));

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending);

    $decisions = $profile->registrationDecisions()->get()->sortBy('id')->values();

    expect($decisions)->toHaveCount(2)
        ->and($decisions[0]->is($originalRejection))->toBeTrue()
        ->and($decisions[0]->status)->toBe(RegistrationStatus::Rejected)
        ->and($decisions[0]->reason)->toBe('Original rejection reason.')
        ->and($decisions[1]->status)->toBe(RegistrationStatus::Pending)
        ->and($decisions[1]->actor_id)->toBe($reviewer->id);
});

test('only pending registrations can be considered for approval or rejection', function () {
    $reviewer = reviewerWithRole('super_admin');
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Rejected,
    ]);

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertStatus(422);
});

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
