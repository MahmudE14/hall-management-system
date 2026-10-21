<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\RegistrationDecision;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationDecision>
 */
class RegistrationDecisionFactory extends Factory
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
            'actor_id' => User::factory(),
            'status' => RegistrationStatus::Approved,
            'reason' => null,
        ];
    }
}
