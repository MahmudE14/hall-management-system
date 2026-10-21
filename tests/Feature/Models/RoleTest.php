<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('role assignment tables contain the required columns', function () {
    expect(Schema::hasColumns('roles', ['id', 'name', 'created_at', 'updated_at']))->toBeTrue();
    expect(Schema::hasColumns('role_user', ['role_id', 'user_id']))->toBeTrue();
});

test('role factories persist roles', function () {
    expect(fn () => Role::factory()->create(['name' => 'student']))->not->toThrow(Throwable::class);

    expect(Role::query()->value('name'))->toBe('student');
});

test('users can hold multiple roles', function () {
    $user = User::factory()->create();
    $roles = Role::factory()->count(2)->create();

    $user->roles()->attach($roles->modelKeys());

    expect($user->roles()->pluck('roles.id')->all())
        ->toEqualCanonicalizing($roles->modelKeys());
});

test('roles can belong to multiple users', function () {
    $role = Role::factory()->create();
    $users = User::factory()->count(2)->create();

    $role->users()->attach($users->modelKeys());

    expect($role->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing($users->modelKeys());
});

test('duplicate role assignments are rejected', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $user->roles()->attach($role);

    expect(fn () => $user->roles()->attach($role))->toThrow(QueryException::class);
    expect($user->roles()->count())->toBe(1);
});
