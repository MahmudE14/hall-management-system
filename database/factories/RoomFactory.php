<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block_id' => Block::factory(),
            'room_no' => fake()->unique()->numerify('###'),
            'floor' => fake()->numberBetween(1, 5),
            'capacity' => fake()->numberBetween(2, 4),
        ];
    }
}
