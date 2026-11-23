<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\Paddle;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Sample data for development. Log in with:
     *   admin@pickleball.test    / password
     *   customer@pickleball.test / password
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@pickleball.test'],
            ['name' => 'Admin', 'phone' => '09170000000', 'password' => 'password'],
        );
        $admin->forceFill(['role' => UserRole::Admin, 'email_verified_at' => now()])->save();

        User::updateOrCreate(
            ['email' => 'customer@pickleball.test'],
            ['name' => 'Juan Dela Cruz', 'phone' => '09171234567', 'password' => 'password'],
        );

        $courts = [
            ['name' => 'Court 1', 'surface_type' => 'Indoor', 'hourly_rate' => 500, 'description' => 'Air-conditioned indoor court.'],
            ['name' => 'Court 2', 'surface_type' => 'Indoor', 'hourly_rate' => 500, 'description' => 'Air-conditioned indoor court.'],
            ['name' => 'Court 3', 'surface_type' => 'Outdoor', 'hourly_rate' => 350, 'description' => 'Covered outdoor court with lights.'],
            ['name' => 'Court 4', 'surface_type' => 'Outdoor', 'hourly_rate' => 300, 'description' => 'Open-air outdoor court.'],
        ];

        foreach ($courts as $court) {
            Court::updateOrCreate(['name' => $court['name']], $court + [
                'opening_time' => '06:00:00',
                'closing_time' => '22:00:00',
                'is_active' => true,
            ]);
        }

        $paddles = [
            ['name' => 'Standard Paddle', 'brand' => 'Franklin', 'hourly_rate' => 50, 'stock_quantity' => 20, 'description' => 'Good all-around paddle for beginners.'],
            ['name' => 'Control Paddle', 'brand' => 'Selkirk', 'hourly_rate' => 100, 'stock_quantity' => 10, 'description' => 'Lightweight paddle for better control.'],
            ['name' => 'Power Paddle', 'brand' => 'JOOLA', 'hourly_rate' => 150, 'stock_quantity' => 6, 'description' => 'Carbon-face paddle for advanced players.'],
        ];

        foreach ($paddles as $paddle) {
            Paddle::updateOrCreate(['name' => $paddle['name']], $paddle + ['is_active' => true]);
        }
    }
}
