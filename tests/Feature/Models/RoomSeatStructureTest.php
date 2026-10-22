<?php

use App\Models\Block;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('room and seat tables contain the required columns', function () {
    expect(Schema::hasColumns('rooms', ['id', 'block_id', 'room_no', 'floor', 'capacity', 'created_at', 'updated_at']))->toBeTrue();
    expect(Schema::hasColumns('seats', ['id', 'room_id', 'label', 'created_at', 'updated_at']))->toBeTrue();
});

test('room and seat factories persist records', function () {
    $block = Block::factory()->create();
    $room = Room::factory()->for($block)->create(['room_no' => '305', 'floor' => 3, 'capacity' => 3]);
    $seat = Seat::factory()->for($room)->create(['label' => 'A']);

    expect($room->refresh()->block_id)->toBe($block->id);
    expect($room->room_no)->toBe('305');
    expect($room->floor)->toBe(3);
    expect($room->capacity)->toBe(3);
    expect($seat->refresh()->room_id)->toBe($room->id);
    expect($seat->label)->toBe('A');
});

test('a block has many rooms', function () {
    $block = Block::factory()->create();
    $rooms = Room::factory()->for($block)->count(2)->create();

    expect($block->rooms()->pluck('id')->all())->toEqualCanonicalizing($rooms->modelKeys());
    expect($block->rooms)->toHaveCount(2);
});

test('a room belongs to a block', function () {
    $room = Room::factory()->create();

    expect($room->block)->toBeInstanceOf(Block::class);
    expect($room->block->is(Block::find($room->block_id)))->toBeTrue();
});

test('a room has many seats', function () {
    $room = Room::factory()->create();
    $seats = Seat::factory()->for($room)->count(3)->sequence(
        ['label' => 'A'],
        ['label' => 'B'],
        ['label' => 'C'],
    )->create();

    expect($room->seats()->pluck('id')->all())->toEqualCanonicalizing($seats->modelKeys());
    expect($room->seats)->toHaveCount(3);
});

test('a seat belongs to a room', function () {
    $seat = Seat::factory()->create();

    expect($seat->room)->toBeInstanceOf(Room::class);
    expect($seat->room->is(Room::find($seat->room_id)))->toBeTrue();
});

test('rooms are removed when their block is deleted', function () {
    $room = Room::factory()->create();

    $room->block->delete();

    expect(Room::find($room->id))->toBeNull();
});

test('seats are removed when their room is deleted', function () {
    $seat = Seat::factory()->create();

    $seat->room->delete();

    expect(Seat::find($seat->id))->toBeNull();
});

test('room numbers are unique within a block', function () {
    $block = Block::factory()->create();
    Room::factory()->for($block)->create(['room_no' => '305']);

    expect(fn () => Room::factory()->for($block)->create(['room_no' => '305']))
        ->toThrow(QueryException::class);
});

test('the same room number can be used in different blocks', function () {
    $firstRoom = Room::factory()->create(['room_no' => '305']);
    $secondRoom = Room::factory()->create(['room_no' => '305']);

    expect($firstRoom->block_id)->not->toBe($secondRoom->block_id);
    expect(Room::where('room_no', '305')->count())->toBe(2);
});

test('seat labels are unique within a room', function () {
    $room = Room::factory()->create();
    Seat::factory()->for($room)->create(['label' => 'A']);

    expect(fn () => Seat::factory()->for($room)->create(['label' => 'A']))
        ->toThrow(QueryException::class);
});

test('the same seat label can be used in different rooms', function () {
    $firstSeat = Seat::factory()->create(['label' => 'A']);
    $secondSeat = Seat::factory()->create(['label' => 'A']);

    expect($firstSeat->room_id)->not->toBe($secondSeat->room_id);
    expect(Seat::where('label', 'A')->count())->toBe(2);
});

test('room capacity must be a positive integer', function (int $capacity) {
    expect(fn () => Room::factory()->create(['capacity' => $capacity]))
        ->toThrow(InvalidArgumentException::class, 'Room capacity must be at least 1.');
})->with([0, -1]);

test('room factory capacities are between two and four', function () {
    $capacities = Room::factory()->count(5)->create()->pluck('capacity');

    $capacities->each(fn (int $capacity) => expect($capacity)->toBeBetween(2, 4));
});
