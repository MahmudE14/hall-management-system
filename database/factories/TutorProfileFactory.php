<?php

namespace Database\Factories;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorProfile>
 */
class TutorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'designation' => fake()->randomElement(['Assistant Professor', 'Associate Professor', 'Lecturer']),
            'department' => fake()->randomElement(['English', 'History', 'Physics']),
            'phone' => fake()->numerify('01#########'),
            'is_active' => true,
        ];
    }
}
