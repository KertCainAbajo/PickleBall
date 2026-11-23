<?php

namespace Database\Factories;

use App\Models\Paddle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paddle>
 */
class PaddleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Paddle',
            'brand' => fake()->randomElement(['Selkirk', 'JOOLA', 'Franklin', 'HEAD']),
            'description' => fake()->sentence(),
            'hourly_rate' => 50,
            'stock_quantity' => 4,
            'is_active' => true,
        ];
    }
}
