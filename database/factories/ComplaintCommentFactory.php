<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintComment>
 */
class ComplaintCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
