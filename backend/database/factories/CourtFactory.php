<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Court '.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(),
            'surface_type' => fake()->randomElement(['Indoor', 'Outdoor']),
            'hourly_rate' => 400,
            'opening_time' => '06:00:00',
            'closing_time' => '22:00:00',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
