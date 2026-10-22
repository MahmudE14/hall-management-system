<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'label' => fake()->randomElement(['A', 'B', 'C', 'D']),
        ];
    }
}
