<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'name' => fake()->unique()->city().' Block',
            'code' => fake()->unique()->bothify('BLK-###'),
        ];
    }
}
