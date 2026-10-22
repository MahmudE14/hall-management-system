<?php

namespace Database\Factories;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_id' => sprintf('KJH-%d-%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'student_profile_id' => StudentProfile::factory(),
            'room_id' => null,
            'location' => null,
            'category' => fake()->randomElement(ComplaintCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'status' => ComplaintStatus::Submitted,
            'assignee_id' => null,
            'assigned_at' => null,
            'resolved_at' => null,
            'resolution_note' => null,
            'rejection_reason' => null,
        ];
    }
}
