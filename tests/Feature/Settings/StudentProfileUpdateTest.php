<?php

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::query()->firstOrCreate(['name' => 'student']);

    $this->student = User::factory()->create([
        'name' => 'Student Name',
        'email' => 'student.profile@student.du.test',
    ]);
    $this->student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
    $this->profile = StudentProfile::factory()->for($this->student)->create([
        'phone' => '01712345678',
    ]);
});

test('students can update their name, email, and phone', function () {
    $response = $this->actingAs($this->student)
        ->from(route('profile.edit'))
        ->patch(route('profile.update', $this->student), [
            'name' => 'Updated Student',
            'email' => 'updated.student@student.du.test',
            'phone' => '01898765432',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $this->student->refresh();

    expect($this->student->name)->toBe('Updated Student')
        ->and($this->student->email)->toBe('updated.student@student.du.test')
        ->and($this->student->email_verified_at)->toBeNull()
        ->and($this->profile->refresh()->phone)->toBe('01898765432');
});

test('students must use a unique student-domain email', function (string $email) {
    User::factory()->create(['email' => 'taken@student.du.test']);

    $this->actingAs($this->student)
        ->from(route('profile.edit'))
        ->patch(route('profile.update', $this->student), [
            'name' => 'Updated Student',
            'email' => $email,
            'phone' => '01712345678',
        ])
        ->assertSessionHasErrors('email');

    expect($this->student->refresh()->email)->toBe('student.profile@student.du.test');
})->with(['outside@example.com', 'taken@student.du.test']);

test('students must provide a valid Bangladeshi phone', function () {
    $this->actingAs($this->student)
        ->from(route('profile.edit'))
        ->patch(route('profile.update', $this->student), [
            'name' => 'Updated Student',
            'email' => $this->student->email,
            'phone' => '12345',
        ])
        ->assertSessionHasErrors('phone');

    expect($this->profile->refresh()->phone)->toBe('01712345678');
});

test('read-only registration and academic fields are ignored', function () {
    $this->actingAs($this->student)
        ->from(route('profile.edit'))
        ->patch(route('profile.update', $this->student), [
            'name' => 'Updated Student',
            'email' => $this->student->email,
            'phone' => '01712345678',
            'registration_number' => '9999999999',
            'department' => 'Mutated Department',
            'session' => '2000-01',
            'registration_status' => 'rejected',
            'residency_status' => 'inactive',
            'is_active' => false,
            'password' => 'attacker-controlled-password',
            'user_id' => User::factory()->create()->id,
        ])
        ->assertSessionHasNoErrors();

    $this->profile->refresh();
    $this->student->refresh();

    expect($this->profile->registration_number)->not->toBe('9999999999')
        ->and($this->profile->department)->not->toBe('Mutated Department')
        ->and($this->profile->session)->not->toBe('2000-01')
        ->and($this->profile->registration_status->value)->not->toBe('rejected')
        ->and($this->student->is_active)->toBeTrue()
        ->and(password_verify('attacker-controlled-password', $this->student->password))->toBeFalse();
});

test('students receive a forbidden response when updating another student profile', function () {
    $other = User::factory()->create([
        'name' => 'Other Student',
        'email' => 'other.student@student.du.test',
    ]);
    $other->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
    $otherProfile = StudentProfile::factory()->for($other)->create([
        'phone' => '01912345678',
    ]);

    $this->actingAs($this->student)
        ->patch(route('profile.update', $other), [
            'name' => 'Compromised Student',
            'email' => 'compromised.student@student.du.test',
            'phone' => '01612345678',
        ])
        ->assertForbidden();

    expect($other->refresh()->name)->toBe('Other Student')
        ->and($other->email)->toBe('other.student@student.du.test')
        ->and($otherProfile->refresh()->phone)->toBe('01912345678');
});

test('staff profiles keep their existing name and email workflow', function () {
    $staff = User::factory()->create(['email' => 'staff@kjh.test']);

    $this->actingAs($staff)
        ->patch(route('profile.update', $staff), [
            'name' => 'Staff Name',
            'email' => 'staff-updated@kjh.test',
        ])
        ->assertSessionHasNoErrors();

    expect($staff->refresh()->name)->toBe('Staff Name')
        ->and($staff->email)->toBe('staff-updated@kjh.test');
});
