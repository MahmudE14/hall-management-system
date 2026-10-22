<?php

use App\Models\Block;
use App\Models\Role;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized users can create tutors', function (string $role) {
    $actor = tutorUserWithRole($role);
    $tutorRole = Role::factory()->create(['name' => 'house_tutor']);

    $this->actingAs($actor)
        ->post(route('house-tutors.store'), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'ayesha.rahman@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'ayesha.rahman@du.test')->firstOrFail();
    $tutor = $user->tutorProfile;

    expect($user->name)->toBe('Dr. Ayesha Rahman')
        ->and($tutor)->toBeInstanceOf(TutorProfile::class)
        ->and($tutor->designation)->toBe('Assistant Professor')
        ->and($tutor->department)->toBe('Physics')
        ->and($tutor->phone)->toBe('01712345678')
        ->and($tutor->is_active)->toBeTrue()
        ->and($user->roles()->whereKey($tutorRole->id)->exists())->toBeTrue();
})->with(['provost', 'super_admin']);

test('authorized users can edit tutors', function (string $role) {
    $tutor = TutorProfile::factory()->create();
    $tutor->user->update(['name' => 'Old Name', 'email' => 'old@du.test']);

    $this->actingAs(tutorUserWithRole($role))
        ->put(route('house-tutors.update', $tutor), [
            'name' => 'Dr. New Name',
            'email' => 'new@du.test',
            'designation' => 'Associate Professor',
            'department' => 'History',
            'phone' => '01812345678',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    expect($tutor->refresh())
        ->designation->toBe('Associate Professor')
        ->department->toBe('History')
        ->phone->toBe('01812345678')
        ->and($tutor->user->refresh())
        ->name->toBe('Dr. New Name')
        ->email->toBe('new@du.test');
})->with(['provost', 'super_admin']);

test('authorized users can assign multiple blocks when creating a tutor', function (string $role) {
    $actor = tutorUserWithRole($role);
    Role::factory()->create(['name' => 'house_tutor']);
    $blocks = Block::factory()->count(2)->create();

    $this->actingAs($actor)
        ->post(route('house-tutors.store'), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'ayesha.rahman@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
            'block_ids' => $blocks->modelKeys(),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $tutor = User::query()->where('email', 'ayesha.rahman@du.test')->firstOrFail()->tutorProfile;

    expect($tutor->blocks()->pluck('blocks.id')->all())
        ->toEqualCanonicalizing($blocks->modelKeys());
})->with(['provost', 'super_admin']);

test('authorized users can assign multiple blocks per tutor and multiple tutors per block', function () {
    $actor = tutorUserWithRole('provost');
    $blockA = Block::factory()->create();
    $blockB = Block::factory()->create();
    $first = TutorProfile::factory()->create();
    $second = TutorProfile::factory()->create();

    $this->actingAs($actor)
        ->put(route('house-tutors.update', $first), [
            'name' => 'Dr. First Tutor',
            'email' => 'first.tutor@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
            'block_ids' => [$blockA->id, $blockB->id],
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $this->actingAs($actor)
        ->put(route('house-tutors.update', $second), [
            'name' => 'Dr. Second Tutor',
            'email' => 'second.tutor@du.test',
            'designation' => 'Associate Professor',
            'department' => 'History',
            'phone' => '01812345678',
            'block_ids' => [$blockA->id],
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    expect($first->blocks()->pluck('blocks.id')->all())
        ->toEqualCanonicalizing([$blockA->id, $blockB->id])
        ->and($second->blocks()->pluck('blocks.id')->all())
        ->toBe([$blockA->id])
        ->and($blockA->tutorProfiles()->pluck('tutor_profiles.id')->all())
        ->toEqualCanonicalizing([$first->id, $second->id]);
});

test('tutor block assignments reject blocks that do not exist', function () {
    $tutor = TutorProfile::factory()->create();

    $this->actingAs(tutorUserWithRole('provost'))
        ->put(route('house-tutors.update', $tutor), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'ayesha.rahman@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
            'block_ids' => [999999],
        ])
        ->assertSessionHasErrors(['block_ids.0']);

    expect($tutor->blocks()->count())->toBe(0);
});

test('authorized users can deactivate tutors', function (string $role) {
    $tutor = TutorProfile::factory()->create(['is_active' => true]);

    $this->actingAs(tutorUserWithRole($role))
        ->patch(route('house-tutors.deactivate', $tutor))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    expect($tutor->refresh()->is_active)->toBeFalse();
    expect($tutor->user->refresh()->is_active)->toBeTrue();
})->with(['provost', 'super_admin']);

test('roles without tutor management access are forbidden from every action', function (string $role) {
    $tutor = TutorProfile::factory()->create();

    $this->actingAs(tutorUserWithRole($role));

    $this->post(route('house-tutors.store'), [
        'name' => 'Dr. Ayesha Rahman',
        'email' => 'ayesha.rahman@du.test',
        'designation' => 'Assistant Professor',
        'department' => 'Physics',
        'phone' => '01712345678',
    ])->assertForbidden();

    $this->put(route('house-tutors.update', $tutor), [
        'name' => 'Dr. New Name',
        'email' => 'new@du.test',
        'designation' => 'Associate Professor',
        'department' => 'History',
        'phone' => '01812345678',
    ])->assertForbidden();

    $this->patch(route('house-tutors.deactivate', $tutor))->assertForbidden();

    expect($tutor->refresh()->is_active)->toBeTrue();
})->with(['hall_office_staff', 'house_tutor', 'club_admin', 'student']);

test('tutor creation requires valid fields', function () {
    $actor = tutorUserWithRole('provost');
    TutorProfile::factory()->create()->user()->update(['email' => 'existing@du.test']);

    $this->actingAs($actor)
        ->post(route('house-tutors.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'designation', 'department', 'phone']);

    $this->actingAs($actor)
        ->post(route('house-tutors.store'), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'not-an-email',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '12345',
        ])
        ->assertSessionHasErrors(['email', 'phone']);

    $this->actingAs($actor)
        ->post(route('house-tutors.store'), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'existing@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
        ])
        ->assertSessionHasErrors(['email']);
});

test('tutor edits require a unique email', function () {
    $tutor = TutorProfile::factory()->create();
    $other = TutorProfile::factory()->create();
    $other->user->update(['email' => 'taken@du.test']);

    $this->actingAs(tutorUserWithRole('provost'))
        ->put(route('house-tutors.update', $tutor), [
            'name' => 'Dr. Ayesha Rahman',
            'email' => 'taken@du.test',
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
        ])
        ->assertSessionHasErrors(['email']);
});

function tutorUserWithRole(string $role): User
{
    $user = User::factory()->create();

    $role = Role::factory()->create(['name' => $role]);

    $user->roles()->attach($role);

    return $user;
}
