<?php

use App\Models\Block;
use App\Models\Hall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('hall and block tables contain the required columns', function () {
    expect(Schema::hasColumns('halls', ['id', 'name', 'created_at', 'updated_at']))->toBeTrue();
    expect(Schema::hasColumns('blocks', ['id', 'hall_id', 'name', 'code', 'created_at', 'updated_at']))->toBeTrue();
});

test('hall and block factories persist records', function () {
    $hall = Hall::factory()->create(['name' => 'Kabi Jasimuddin Hall']);
    $block = Block::factory()->for($hall)->create(['name' => 'North Block', 'code' => 'NORTH']);

    expect($hall->refresh()->name)->toBe('Kabi Jasimuddin Hall');
    expect($block->refresh()->hall_id)->toBe($hall->id);
    expect($block->name)->toBe('North Block');
    expect($block->code)->toBe('NORTH');
});

test('a hall has many blocks', function () {
    $hall = Hall::factory()->create();
    $blocks = Block::factory()->for($hall)->count(2)->create();

    expect($hall->blocks()->pluck('id')->all())->toEqualCanonicalizing($blocks->modelKeys());
    expect($hall->blocks)->toHaveCount(2);
});

test('a block belongs to a hall', function () {
    $block = Block::factory()->create();

    expect($block->hall)->toBeInstanceOf(Hall::class);
    expect($block->hall->is(Hall::find($block->hall_id)))->toBeTrue();
});

test('blocks are removed when their hall is deleted', function () {
    $block = Block::factory()->create();

    $block->hall->delete();

    expect(Block::find($block->id))->toBeNull();
});
