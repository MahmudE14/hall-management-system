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

    $this->actingAs(userWithRole($role))
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
    $this->actingAs(userWithRole('student'))
        ->get(route('registrations.index'))
        ->assertForbidden();
});

test('a reviewer can approve a pending registration and history is retained', function () {
    $reviewer = userWithRole('hall_office_staff');
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
    $reviewer = userWithRole('provost');
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
    $reviewer = userWithRole('super_admin');
    $profile = StudentProfile::factory()->create();

    $this->actingAs($reviewer)
        ->post(route('registrations.reject', $profile), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($profile->refresh()->registration_status)->toBe(RegistrationStatus::Pending);
    expect($profile->registrationDecisions()->count())->toBe(0);
});

test('a decided registration cannot be decided again', function () {
    $reviewer = userWithRole('hall_office_staff');
    $profile = StudentProfile::factory()->create([
        'registration_status' => RegistrationStatus::Approved,
    ]);

    $this->actingAs($reviewer)
        ->post(route('registrations.approve', $profile))
        ->assertStatus(422);

    expect($profile->registrationDecisions()->count())->toBe(0);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => $role]));

    return $user;
}
