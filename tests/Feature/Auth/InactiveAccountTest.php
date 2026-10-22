<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkeys;

uses(RefreshDatabase::class);

test('inactive users cannot authenticate with valid credentials', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('active users can still authenticate', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('inactive users cannot authenticate with a passkey', function () {
    $user = User::factory()->create(['is_active' => false]);
    $passkey = $user->passkeys()->create([
        'name' => 'Test passkey',
        'credential_id' => 'inactive-user-credential',
        'credential' => [],
    ]);

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), $passkey))
        ->toBeFalse();
});

test('active users can authenticate with a passkey', function () {
    $user = User::factory()->create();
    $passkey = $user->passkeys()->create([
        'name' => 'Test passkey',
        'credential_id' => 'active-user-credential',
        'credential' => [],
    ]);

    expect(Passkeys::allowsLogin(Request::create('/passkeys/login', 'POST'), $passkey))
        ->toBeTrue();
});

test('deactivation during a two factor challenge prevents authentication', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $user->update(['is_active' => false]);

    $this->post(route('two-factor.login'), [
        'recovery_code' => 'recovery-code-1',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a deactivated session loses access to protected routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('reactivated users regain access', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();

    $user->update(['is_active' => true]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});
