<?php

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('student profiles contain identity and status columns', function () {
    expect(Schema::hasColumns('student_profiles', [
        'id',
        'user_id',
        'registration_number',
        'department',
        'session',
        'phone',
        'registration_status',
        'residency_status',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('registration statuses cover the approval lifecycle', function () {
    expect(array_column(RegistrationStatus::cases(), 'value'))
        ->toBe(['pending', 'approved', 'rejected']);
});

test('residency statuses distinguish attached and resident students', function () {
    expect(array_column(ResidencyStatus::cases(), 'value'))
        ->toBe(['attached', 'resident']);
});

test('student profile factories create pending attached students with hashed passwords', function () {
    $profile = StudentProfile::factory()->create()->refresh();

    expect($profile->registration_status)->toBe(RegistrationStatus::Pending);
    expect($profile->residency_status)->toBe(ResidencyStatus::Attached);
    expect($profile->user)->toBeInstanceOf(User::class);
    expect($profile->user->password)->not->toBe('password');
    expect(Hash::check('password', $profile->user->password))->toBeTrue();
});

test('users expose their student profile', function () {
    $profile = StudentProfile::factory()->create();

    expect($profile->user->studentProfile->is($profile))->toBeTrue();
});

test('users cannot have more than one student profile', function () {
    $user = User::factory()->create();
    StudentProfile::factory()->for($user)->create();

    expect(fn () => StudentProfile::factory()->for($user)->create())
        ->toThrow(QueryException::class);
});
