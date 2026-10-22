<?php

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('the six documented demo accounts can authenticate', function (string $email) {
    $this->post(route('login.store'), [
        'email' => $email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
})->with([
    'admin@kjh.test',
    'provost@kjh.test',
    'office@kjh.test',
    'tutor@kjh.test',
    'clubadmin@student.du.test',
    'student@student.du.test',
]);

test('demo accounts have their documented roles', function (string $email, array $roles) {
    $user = User::query()->where('email', $email)->firstOrFail();

    expect($user->roles()->pluck('name')->all())->toEqualCanonicalizing($roles);
})->with([
    ['admin@kjh.test', ['super_admin']],
    ['provost@kjh.test', ['provost']],
    ['office@kjh.test', ['hall_office_staff']],
    ['tutor@kjh.test', ['house_tutor']],
    ['clubadmin@student.du.test', ['club_admin', 'student']],
    ['student@student.du.test', ['student']],
]);

test('demo passwords are hashed', function () {
    $user = User::query()->where('email', 'admin@kjh.test')->firstOrFail();

    expect($user->password)->not->toBe('password')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('demo student identities have approved profiles', function () {
    $clubAdmin = User::query()->where('email', 'clubadmin@student.du.test')->firstOrFail();
    $student = User::query()->where('email', 'student@student.du.test')->firstOrFail();

    expect($clubAdmin->studentProfile->registration_status)->toBe(RegistrationStatus::Approved)
        ->and($student->studentProfile->registration_status)->toBe(RegistrationStatus::Approved)
        ->and($student->studentProfile->residency_status)->toBe(ResidencyStatus::Resident);
});

test('reseeding reactivates deactivated demo accounts without duplicating them', function () {
    $demoEmails = [
        'admin@kjh.test',
        'provost@kjh.test',
        'office@kjh.test',
        'tutor@kjh.test',
        'clubadmin@student.du.test',
        'student@student.du.test',
    ];
    $deactivated = User::query()->where('email', 'tutor@kjh.test')->firstOrFail();
    $deactivated->update(['is_active' => false]);
    User::factory()->create(['email' => 'unrelated@example.test']);

    $this->seed(DatabaseSeeder::class);

    expect($deactivated->refresh()->is_active)->toBeTrue()
        ->and(User::query()->whereIn('email', $demoEmails)->count())->toBe(6)
        ->and(User::query()->where('email', 'unrelated@example.test')->exists())->toBeTrue();
});
