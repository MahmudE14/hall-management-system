<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
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
            'registration_number' => fake()->unique()->numerify('##########'),
            'department' => fake()->randomElement(['English', 'History', 'Physics']),
            'session' => '2023-24',
            'phone' => fake()->numerify('01#########'),
        ];
    }
}
