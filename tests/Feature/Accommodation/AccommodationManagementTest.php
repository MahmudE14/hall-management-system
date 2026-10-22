<?php

use App\Models\Block;
use App\Models\Hall;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('structure viewers can list blocks and rooms', function (string $role) {
    $viewer = accommodationUserWithRole($role);
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create();
    Seat::factory()->for($room)->create(['label' => 'A']);

    $this->actingAs($viewer)
        ->get(route('blocks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('blocks/index', false)
            ->has('blocks', 1)
            ->where('blocks.0.id', $block->id)
        );

    $this->actingAs($viewer)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/index', false)
            ->has('rooms', 1)
            ->where('rooms.0.id', $room->id)
            ->has('rooms.0.seats', 1)
        );
})->with(['hall_office_staff', 'provost', 'super_admin']);

test('roles without structure access are forbidden from every management action', function (string $role) {
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create();
    $seat = Seat::factory()->for($room)->create(['label' => 'A']);
    $actor = accommodationUserWithRole($role);

    $this->actingAs($actor);

    $this->get(route('blocks.index'))->assertForbidden();
    $this->get(route('rooms.index'))->assertForbidden();
    $this->get(route('blocks.create'))->assertForbidden();
    $this->get(route('blocks.edit', $block))->assertForbidden();
    $this->get(route('rooms.create'))->assertForbidden();
    $this->get(route('rooms.edit', $room))->assertForbidden();
    $this->post(route('blocks.store'), ['name' => 'North Block', 'code' => 'NORTH'])->assertForbidden();
    $this->put(route('blocks.update', $block), ['name' => 'South Block', 'code' => 'SOUTH'])->assertForbidden();
    $this->post(route('rooms.store'), [
        'block_id' => $block->id,
        'room_no' => '305',
        'floor' => 3,
        'capacity' => 3,
    ])->assertForbidden();
    $this->put(route('rooms.update', $room), [
        'room_no' => '306',
        'floor' => 3,
        'capacity' => 4,
    ])->assertForbidden();
    $this->post(route('seats.store', $room), ['label' => 'B'])->assertForbidden();
    $this->put(route('seats.update', $seat), ['label' => 'B'])->assertForbidden();

    $this->assertDatabaseHas('blocks', ['id' => $block->id, 'name' => $block->name]);
    $this->assertDatabaseHas('rooms', ['id' => $room->id, 'room_no' => $room->room_no]);
    $this->assertDatabaseHas('seats', ['id' => $seat->id, 'label' => 'A']);
})->with(['house_tutor', 'club_admin', 'student']);

test('provost can view structure but cannot create or edit it', function () {
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create();
    $seat = Seat::factory()->for($room)->create(['label' => 'A']);
    $provost = accommodationUserWithRole('provost');

    $this->actingAs($provost);

    $this->get(route('blocks.index'))->assertOk();
    $this->get(route('rooms.index'))->assertOk();
    $this->get(route('blocks.create'))->assertForbidden();
    $this->get(route('blocks.edit', $block))->assertForbidden();
    $this->get(route('rooms.create'))->assertForbidden();
    $this->get(route('rooms.edit', $room))->assertForbidden();
    $this->post(route('blocks.store'), ['name' => 'North Block', 'code' => 'NORTH'])->assertForbidden();
    $this->put(route('blocks.update', $block), ['name' => 'South Block', 'code' => 'SOUTH'])->assertForbidden();
    $this->post(route('rooms.store'), [
        'block_id' => $block->id,
        'room_no' => '305',
        'floor' => 3,
        'capacity' => 3,
    ])->assertForbidden();
    $this->put(route('rooms.update', $room), [
        'room_no' => '306',
        'floor' => 3,
        'capacity' => 4,
    ])->assertForbidden();
    $this->post(route('seats.store', $room), ['label' => 'B'])->assertForbidden();
    $this->put(route('seats.update', $seat), ['label' => 'B'])->assertForbidden();
});

test('structure managers can create blocks', function (string $role) {
    $hall = Hall::factory()->create();

    $this->actingAs(accommodationUserWithRole($role))
        ->post(route('blocks.store'), [
            'name' => 'North Block',
            'code' => 'NORTH',
        ])
        ->assertRedirect(route('blocks.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('blocks', [
        'hall_id' => $hall->id,
        'name' => 'North Block',
        'code' => 'NORTH',
    ]);
})->with(['hall_office_staff', 'super_admin']);

test('structure managers can update blocks', function (string $role) {
    $block = Block::factory()->create(['name' => 'North Block', 'code' => 'NORTH']);

    $this->actingAs(accommodationUserWithRole($role))
        ->put(route('blocks.update', $block), [
            'name' => 'South Block',
            'code' => 'SOUTH',
        ])
        ->assertRedirect(route('blocks.index'))
        ->assertSessionHasNoErrors();

    expect($block->refresh())
        ->name->toBe('South Block')
        ->code->toBe('SOUTH')
        ->hall_id->toBe($block->hall_id);
})->with(['hall_office_staff', 'super_admin']);

test('structure managers can create and update rooms', function (string $role) {
    $block = Block::factory()->create();
    $manager = accommodationUserWithRole($role);

    $this->actingAs($manager)
        ->post(route('rooms.store'), [
            'block_id' => $block->id,
            'room_no' => '305',
            'floor' => 3,
            'capacity' => 3,
        ])
        ->assertRedirect(route('rooms.index'))
        ->assertSessionHasNoErrors();

    $room = Room::query()->where('room_no', '305')->firstOrFail();

    expect($room->block_id)->toBe($block->id)
        ->and($room->floor)->toBe(3)
        ->and($room->capacity)->toBe(3);

    $this->actingAs($manager)
        ->put(route('rooms.update', $room), [
            'room_no' => '305',
            'floor' => 4,
            'capacity' => 4,
        ])
        ->assertRedirect(route('rooms.index'))
        ->assertSessionHasNoErrors();

    expect($room->refresh())
        ->room_no->toBe('305')
        ->floor->toBe(4)
        ->capacity->toBe(4);
})->with(['hall_office_staff', 'super_admin']);

test('structure managers can create and update seats', function (string $role) {
    $room = Room::factory()->create(['capacity' => 3]);
    $manager = accommodationUserWithRole($role);

    $this->actingAs($manager)
        ->post(route('seats.store', $room), ['label' => 'A'])
        ->assertRedirect(route('rooms.edit', $room))
        ->assertSessionHasNoErrors();

    $seat = Seat::query()->where('room_id', $room->id)->where('label', 'A')->firstOrFail();

    $this->actingAs($manager)
        ->put(route('seats.update', $seat), ['label' => 'B'])
        ->assertRedirect(route('rooms.edit', $room))
        ->assertSessionHasNoErrors();

    expect($seat->refresh())
        ->room_id->toBe($room->id)
        ->label->toBe('B');
})->with(['hall_office_staff', 'super_admin']);

test('block and room management validate required fields', function () {
    $manager = accommodationUserWithRole('hall_office_staff');

    $this->actingAs($manager)
        ->post(route('blocks.store'), [])
        ->assertSessionHasErrors(['name', 'code']);

    $this->actingAs($manager)
        ->post(route('rooms.store'), [])
        ->assertSessionHasErrors(['block_id', 'room_no', 'floor', 'capacity']);
});

test('rooms require a positive capacity and a room number unique within their block', function () {
    $manager = accommodationUserWithRole('hall_office_staff');
    $block = Block::factory()->create();
    Room::factory()->for($block)->create(['room_no' => '305']);

    $this->actingAs($manager)
        ->post(route('rooms.store'), [
            'block_id' => $block->id,
            'room_no' => '305',
            'floor' => 3,
            'capacity' => 0,
        ])
        ->assertSessionHasErrors(['room_no', 'capacity']);

    $this->assertDatabaseCount('rooms', 1);
});

test('seat labels are required and unique within their room', function () {
    $manager = accommodationUserWithRole('hall_office_staff');
    $room = Room::factory()->create(['capacity' => 3]);
    Seat::factory()->for($room)->create(['label' => 'A']);

    $this->actingAs($manager)
        ->post(route('seats.store', $room), [])
        ->assertSessionHasErrors('label');

    $this->actingAs($manager)
        ->post(route('seats.store', $room), ['label' => 'A'])
        ->assertSessionHasErrors('label');

    $this->assertDatabaseCount('seats', 1);
});

function accommodationUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}
