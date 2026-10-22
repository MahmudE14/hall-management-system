<?php

use App\Models\Block;
use App\Models\Role;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('authorized users can view the tutor administration page', function (string $role) {
    $block = Block::factory()->create(['name' => 'North Block', 'code' => 'NORTH']);
    $tutor = TutorProfile::factory()->create();
    $tutor->user->update([
        'name' => 'Dr. Ayesha Rahman',
        'email' => 'ayesha.rahman@du.test',
    ]);
    $tutor->blocks()->attach($block);

    $this->actingAs(tutorPageUserWithRole($role))
        ->get(route('house-tutors.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('house-tutors/index', false)
            ->has('tutors', 1)
            ->where('tutors.0.id', $tutor->id)
            ->where('tutors.0.name', 'Dr. Ayesha Rahman')
            ->where('tutors.0.email', 'ayesha.rahman@du.test')
            ->where('tutors.0.designation', $tutor->designation)
            ->where('tutors.0.department', $tutor->department)
            ->where('tutors.0.phone', $tutor->phone)
            ->where('tutors.0.is_active', true)
            ->where('tutors.0.blocks.0.id', $block->id)
            ->missing('tutors.0.password')
        );
})->with(['provost', 'super_admin']);

test('authorized users can open the create tutor page with block options', function (string $role) {
    $blocks = Block::factory()->count(2)->create();

    $this->actingAs(tutorPageUserWithRole($role))
        ->get(route('house-tutors.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('house-tutors/create', false)
            ->has('blocks', 2)
            ->where('blocks', fn ($options) => collect($options)->pluck('id')->sort()->values()->all()
                === collect($blocks->modelKeys())->sort()->values()->all())
        );
})->with(['provost', 'super_admin']);

test('authorized users can open the edit tutor page with block assignments', function (string $role) {
    $tutor = TutorProfile::factory()->create();
    $tutor->user->update(['name' => 'Dr. Ayesha Rahman']);
    $blocks = Block::factory()->count(2)->create();
    $tutor->blocks()->attach($blocks);

    $this->actingAs(tutorPageUserWithRole($role))
        ->get(route('house-tutors.edit', $tutor))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('house-tutors/edit', false)
            ->where('tutor.id', $tutor->id)
            ->where('tutor.name', 'Dr. Ayesha Rahman')
            ->where('tutor.block_ids', fn ($ids) => collect($ids)->sort()->values()->all()
                === collect($blocks->modelKeys())->sort()->values()->all())
            ->has('blocks', 2)
            ->missing('tutor.password')
        );
})->with(['provost', 'super_admin']);

test('roles without tutor management access are forbidden from the tutor administration pages', function (string $role) {
    $tutor = TutorProfile::factory()->create();

    $this->actingAs(tutorPageUserWithRole($role));

    $this->get(route('house-tutors.index'))->assertForbidden();
    $this->get(route('house-tutors.create'))->assertForbidden();
    $this->get(route('house-tutors.edit', $tutor))->assertForbidden();
})->with(['hall_office_staff', 'house_tutor', 'club_admin', 'student']);

function tutorPageUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => $role]);
    $user->roles()->attach($role);

    return $user;
}
