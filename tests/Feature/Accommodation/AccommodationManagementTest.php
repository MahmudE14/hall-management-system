<?php

use App\Models\Block;
use App\Models\Hall;
use App\Models\Role;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatAllocation;
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
            ->where('blocks.0.rooms_count', 1)
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

test('structure list pages expose role-appropriate management controls', function (string $role, bool $canManage) {
    $viewer = accommodationUserWithRole($role);

    $this->actingAs($viewer)
        ->get(route('blocks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('blocks/index', false)
            ->where('canManage', $canManage)
        );

    $this->actingAs($viewer)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/index', false)
            ->where('canManage', $canManage)
        );
})->with([
    ['hall_office_staff', true],
    ['super_admin', true],
    ['provost', false],
]);

test('room pages report occupancy from active allocations', function () {
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create(['capacity' => 4]);
    $seats = Seat::factory()->for($room)->count(3)->sequence(
        ['label' => 'A'],
        ['label' => 'B'],
        ['label' => 'C'],
    )->create();
    SeatAllocation::factory()->for($seats[0])->create();
    SeatAllocation::factory()->for($seats[1])->create();
    $manager = accommodationUserWithRole('hall_office_staff');

    $this->actingAs($manager)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/index', false)
            ->has('rooms', 1)
            ->where('rooms.0.id', $room->id)
            ->where('rooms.0.capacity', 4)
            ->where('rooms.0.seats_count', 3)
            ->where('rooms.0.occupancy_available', true)
            ->where('rooms.0.occupied_seats', 2)
            ->where('rooms.0.free_seats', 2)
            ->where('rooms.0.block.id', $block->id)
            ->has('rooms.0.seats', 3)
        );

    $this->actingAs($manager)
        ->get(route('rooms.edit', $room))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/edit', false)
            ->where('room.occupancy_available', true)
            ->where('room.occupied_seats', 2)
            ->where('room.free_seats', 2)
        );
});

test('block and room create and edit pages load their form props', function () {
    $block = Block::factory()->create(['name' => 'North Block', 'code' => 'NORTH']);
    $room = Room::factory()->for($block)->create(['room_no' => '305', 'floor' => 3, 'capacity' => 3]);
    Seat::factory()->for($room)->create(['label' => 'A']);
    $manager = accommodationUserWithRole('hall_office_staff');

    $this->actingAs($manager)
        ->get(route('blocks.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('blocks/create', false)
        );

    $this->actingAs($manager)
        ->get(route('blocks.edit', $block))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('blocks/edit', false)
            ->where('block.id', $block->id)
            ->where('block.name', 'North Block')
            ->where('block.code', 'NORTH')
        );

    $this->actingAs($manager)
        ->get(route('rooms.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/create', false)
            ->has('blocks', 1)
            ->where('blocks.0.id', $block->id)
        );

    $this->actingAs($manager)
        ->get(route('rooms.edit', $room))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('rooms/edit', false)
            ->where('room.id', $room->id)
            ->where('room.room_no', '305')
            ->where('room.capacity', 3)
            ->where('room.block.id', $block->id)
            ->has('room.seats', 1)
        );
});

test('room form validation redirects back with field errors', function () {
    $block = Block::factory()->create();

    $this->actingAs(accommodationUserWithRole('hall_office_staff'))
        ->from(route('rooms.create'))
        ->post(route('rooms.store'), [
            'block_id' => $block->id,
            'room_no' => '',
            'floor' => 1,
            'capacity' => 0,
        ])
        ->assertRedirect(route('rooms.create'))
        ->assertSessionHasErrors(['room_no', 'capacity']);
});

test('seat managers can add seats up to room capacity but not beyond it', function (string $role) {
    $room = Room::factory()->create(['capacity' => 2]);
    Seat::factory()->for($room)->create(['label' => 'A']);
    $manager = accommodationUserWithRole($role);

    $this->actingAs($manager)
        ->post(route('seats.store', $room), ['label' => 'B'])
        ->assertRedirect(route('rooms.edit', $room))
        ->assertSessionHasNoErrors();

    expect($room->seats()->count())->toBe(2);

    $this->actingAs($manager)
        ->post(route('seats.store', $room), ['label' => 'C'])
        ->assertSessionHasErrors([
            'label' => 'This room already has the maximum number of seats allowed by its capacity.',
        ]);

    expect($room->seats()->count())->toBe(2);
})->with(['hall_office_staff', 'super_admin']);

test('room capacity cannot be reduced below the seats already defined', function () {
    $room = Room::factory()->create(['capacity' => 3]);
    Seat::factory()->for($room)->count(2)->sequence(
        ['label' => 'A'],
        ['label' => 'B'],
    )->create();
    $manager = accommodationUserWithRole('hall_office_staff');

    $this->actingAs($manager)
        ->put(route('rooms.update', $room), [
            'room_no' => $room->room_no,
            'floor' => $room->floor,
            'capacity' => 1,
        ])
        ->assertSessionHasErrors([
            'capacity' => 'Capacity cannot be lower than the seats already defined or currently occupied (2).',
        ]);

    expect($room->refresh()->capacity)->toBe(3);

    $this->actingAs($manager)
        ->put(route('rooms.update', $room), [
            'room_no' => $room->room_no,
            'floor' => $room->floor,
            'capacity' => 2,
        ])
        ->assertRedirect(route('rooms.index'))
        ->assertSessionHasNoErrors();

    expect($room->refresh()->capacity)->toBe(2);
});

test('editing a seat label preserves the seat identity and room capacity', function () {
    $room = Room::factory()->create(['capacity' => 3]);
    $seat = Seat::factory()->for($room)->create(['label' => 'A']);
    $seatId = $seat->id;

    $this->actingAs(accommodationUserWithRole('hall_office_staff'))
        ->put(route('seats.update', $seat), ['label' => 'B'])
        ->assertRedirect(route('rooms.edit', $room))
        ->assertSessionHasNoErrors();

    expect($seat->refresh())
        ->id->toBe($seatId)
        ->label->toBe('B')
        ->room_id->toBe($room->id);
    expect($room->refresh()->capacity)->toBe(3);
    expect($room->seats()->count())->toBe(1);
});

test('seat label updates remain unique within their room', function () {
    $room = Room::factory()->create(['capacity' => 3]);
    Seat::factory()->for($room)->create(['label' => 'A']);
    $seat = Seat::factory()->for($room)->create(['label' => 'B']);

    $this->actingAs(accommodationUserWithRole('hall_office_staff'))
        ->put(route('seats.update', $seat), ['label' => 'A'])
        ->assertSessionHasErrors('label');

    expect($seat->refresh()->label)->toBe('B');
});

function accommodationUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}
