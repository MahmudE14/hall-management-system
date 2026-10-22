<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatAllocation>
 */
class SeatAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_profile_id' => StudentProfile::factory(),
            'seat_id' => Seat::factory(),
            'allocated_at' => now()->toDateString(),
            'vacated_at' => null,
            'vacancy_reason' => null,
            'active' => true,
        ];
    }
}
