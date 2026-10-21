<?php

use App\Enums\RegistrationStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('pending students are denied approval-gated capabilities', function () {
    Route::middleware(['approved.student'])->get('/test/approved-capability', fn () => 'ok');

    $this->actingAs(studentWithStatus(RegistrationStatus::Pending))
        ->get('/test/approved-capability')
        ->assertForbidden();
});

test('rejected students are denied approval-gated capabilities', function () {
    Route::middleware(['approved.student'])->get('/test/approved-capability', fn () => 'ok');

    $this->actingAs(studentWithStatus(RegistrationStatus::Rejected))
        ->get('/test/approved-capability')
        ->assertForbidden();
});

test('approved students may use approval-gated capabilities', function () {
    Route::middleware(['approved.student'])->get('/test/approved-capability', fn () => 'ok');

    $this->actingAs(studentWithStatus(RegistrationStatus::Approved))
        ->get('/test/approved-capability')
        ->assertOk();
});

test('users without a student profile are denied approval-gated capabilities', function () {
    Route::middleware(['approved.student'])->get('/test/approved-capability', fn () => 'ok');

    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));

    $this->actingAs($user)
        ->get('/test/approved-capability')
        ->assertForbidden();
});

test('users are authorized when they hold one of the required roles', function () {
    Route::middleware(['role:super_admin,hall_office_staff'])->get('/test/roles', fn () => 'ok');

    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'hall_office_staff']));

    $this->actingAs($user)
        ->get('/test/roles')
        ->assertOk();
});

test('users are forbidden when they hold none of the required roles', function () {
    Route::middleware(['role:super_admin'])->get('/test/roles', fn () => 'ok');

    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));

    $this->actingAs($user)
        ->get('/test/roles')
        ->assertForbidden();
});

test('multi-role users are authorized by any assigned role', function () {
    Route::middleware(['role:club_admin'])->get('/test/roles', fn () => 'ok');

    $user = studentWithStatus(RegistrationStatus::Approved);
    $user->roles()->attach(Role::factory()->create(['name' => 'club_admin']));

    $this->actingAs($user)
        ->get('/test/roles')
        ->assertOk();
});

function studentWithStatus(RegistrationStatus $status): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));
    StudentProfile::factory()->for($user)->create(['registration_status' => $status]);

    return $user;
}
