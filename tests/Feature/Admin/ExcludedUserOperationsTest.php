<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::query()->firstOrCreate(['name' => 'super_admin']);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::query()->where('name', 'super_admin')->firstOrFail());
    $this->target = User::factory()->create();
});

test('super admins cannot delete users administratively', function () {
    $this->actingAs($this->admin)
        ->delete("/admin/users/{$this->target->id}")
        ->assertNotFound();

    expect(User::query()->find($this->target->id))->not->toBeNull();
});

test('super admins cannot create users administratively', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.index'), [
            'name' => 'Created By Admin',
            'email' => 'created@student.du.test',
        ])
        ->assertStatus(405);

    $this->assertDatabaseMissing('users', ['email' => 'created@student.du.test']);
});

test('super admins cannot mutate unspecified user fields', function () {
    $originalName = $this->target->name;

    $this->actingAs($this->admin)
        ->patch("/admin/users/{$this->target->id}", ['name' => 'Mutated'])
        ->assertNotFound();

    expect($this->target->refresh()->name)->toBe($originalName);
});

test('super admins cannot reset another user password administratively', function () {
    $originalPassword = $this->target->password;

    $this->actingAs($this->admin)
        ->post("/admin/users/{$this->target->id}/password", [
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])
        ->assertNotFound();

    expect(Hash::check('password', $this->target->refresh()->password))->toBeTrue()
        ->and($this->target->password)->toBe($originalPassword);
});
