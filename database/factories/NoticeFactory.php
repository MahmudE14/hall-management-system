<?php

namespace Database\Factories;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'category' => fake()->randomElement(NoticeCategory::cases()),
            'attachment_path' => null,
            'status' => NoticeStatus::Draft,
            'is_pinned' => false,
            'published_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NoticeStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_pinned' => true,
        ]);
    }
}
