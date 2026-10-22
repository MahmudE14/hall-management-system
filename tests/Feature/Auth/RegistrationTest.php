<?php

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('valid students can register with a pending profile', function () {
    Role::factory()->create(['name' => 'student']);

    $response = $this->post(route('register.store'), studentRegistrationInput());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@student.du.test')->firstOrFail();

    expect(auth()->user()?->is_active)->toBeTrue()
        ->and($user->is_active)->toBeTrue();

    $this->get(route('profile.edit'))->assertOk();

    expect($user->studentProfile)->not->toBeNull();
    expect($user->studentProfile->registration_number)->toBe('2023000001');
    expect($user->studentProfile->department)->toBe('English');
    expect($user->studentProfile->session)->toBe('2023-24');
    expect($user->studentProfile->phone)->toBe('01712345678');
    expect($user->studentProfile->registration_status)->toBe(RegistrationStatus::Pending);
    expect($user->studentProfile->residency_status)->toBe(ResidencyStatus::Attached);
    expect($user->roles()->pluck('name')->all())->toBe(['student']);
    expect(Hash::check('password', $user->password))->toBeTrue();
});

test('registration requires every identity field', function () {
    $response = $this->post(route('register.store'), []);

    $response->assertSessionHasErrors([
        'name',
        'registration_number',
        'department',
        'session',
        'phone',
        'email',
        'password',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('registration rejects an email outside the student domain', function (string $email) {
    Role::factory()->create(['name' => 'student']);

    $response = $this->post(route('register.store'), studentRegistrationInput([
        'email' => $email,
    ]));

    $response->assertSessionHasErrors([
        'email' => 'Use your @student.du.test email address.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'another domain' => 'test@example.com',
    'lookalike suffix' => 'test@student.du.test.example.com',
    'subdomain' => 'test@sub.student.du.test',
]);

test('registration rejects an invalid Bangladeshi phone number', function (string $phone) {
    Role::factory()->create(['name' => 'student']);

    $response = $this->post(route('register.store'), studentRegistrationInput([
        'phone' => $phone,
    ]));

    $response->assertSessionHasErrors([
        'phone' => 'Enter a valid Bangladeshi mobile number.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'too short' => '0171234567',
    'too long' => '017123456789',
    'missing mobile prefix' => '02712345678',
    'non-numeric' => '0171234567a',
]);

test('registration rejects a registration number that is not exactly ten digits', function (string $registrationNumber) {
    Role::factory()->create(['name' => 'student']);

    $response = $this->post(route('register.store'), studentRegistrationInput([
        'registration_number' => $registrationNumber,
    ]));

    $response->assertSessionHasErrors([
        'registration_number' => 'Enter a 10-digit registration number.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'too short' => '123456789',
    'too long' => '12345678901',
    'not numeric' => 'ABCDEFGHIJ',
]);

test('registration rejects a duplicate email', function () {
    Role::factory()->create(['name' => 'student']);
    User::factory()->create(['email' => 'test@student.du.test']);

    $response = $this->post(route('register.store'), studentRegistrationInput());

    $response->assertSessionHasErrors([
        'email' => 'This email address is already registered.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration rejects a duplicate registration number', function () {
    Role::factory()->create(['name' => 'student']);
    StudentProfile::factory()->create(['registration_number' => '2023000001']);

    $response = $this->post(route('register.store'), studentRegistrationInput([
        'email' => 'other@student.du.test',
    ]));

    $response->assertSessionHasErrors([
        'registration_number' => 'This registration number is already registered.',
    ]);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function studentRegistrationInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'registration_number' => '2023000001',
        'department' => 'English',
        'session' => '2023-24',
        'phone' => '01712345678',
        'email' => 'test@student.du.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}
