<?php

use App\Models\Block;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('tutor tables contain the required columns', function () {
    expect(Schema::hasColumns('tutor_profiles', [
        'id',
        'user_id',
        'designation',
        'department',
        'phone',
        'is_active',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('block_tutor_profile', [
        'block_id',
        'tutor_profile_id',
    ]))->toBeTrue();
});

test('tutor profile factories persist records', function () {
    $tutor = TutorProfile::factory()->create([
        'designation' => 'Assistant Professor',
        'department' => 'Physics',
        'phone' => '01712345678',
    ]);

    expect($tutor->refresh())
        ->designation->toBe('Assistant Professor')
        ->department->toBe('Physics')
        ->phone->toBe('01712345678')
        ->and($tutor->is_active)->toBeTrue();
});

test('a tutor profile belongs to a user and a user has one tutor profile', function () {
    $user = User::factory()->create();
    $tutor = TutorProfile::factory()->for($user)->create();

    expect($tutor->user->is($user))->toBeTrue();
    expect($user->refresh()->tutorProfile->is($tutor))->toBeTrue();
});

test('blocks and tutor profiles form a many-to-many relationship', function () {
    $tutor = TutorProfile::factory()->create();
    $blocks = Block::factory()->count(2)->create();

    $tutor->blocks()->attach($blocks->modelKeys());

    expect($tutor->blocks()->pluck('blocks.id')->all())
        ->toEqualCanonicalizing($blocks->modelKeys());
    expect($blocks->first()->tutorProfiles()->pluck('tutor_profiles.id')->all())
        ->toContain($tutor->id);
});

test('duplicate tutor block assignments are rejected', function () {
    $tutor = TutorProfile::factory()->create();
    $block = Block::factory()->create();

    $tutor->blocks()->attach($block);

    expect(fn () => $tutor->blocks()->attach($block))
        ->toThrow(QueryException::class);
    expect($tutor->blocks()->count())->toBe(1);
});

test('tutor profiles are removed when their user is deleted', function () {
    $tutor = TutorProfile::factory()->create();

    $tutor->user->delete();

    expect(TutorProfile::find($tutor->id))->toBeNull();
});
