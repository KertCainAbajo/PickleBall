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

        foreach ($courts as $i => $data) {
            $court = Court::firstOrNew(['name' => $data['name']]);
            $court->fill($data + [
                'opening_time' => '06:00:00',
                'closing_time' => '22:00:00',
                'is_active' => true,
            ]);
            // Default photo (public/images/courts); never replace one an admin uploaded.
            if (! $court->image_path || str_starts_with($court->image_path, 'images/courts/')) {
                $court->image_path = 'images/courts/court-'.($i + 1).'.jpg';
            }
            $court->save();
        }

        // Brand product photos ship in public/images/paddles; admins can replace them in Admin > Paddles.
        // 'was' = the old sample name, so re-seeding renames existing rows instead of adding duplicates.
        $paddles = [
            ['was' => 'Standard Paddle', 'name' => 'C45 Dynasty', 'brand' => 'Franklin', 'hourly_rate' => 50, 'stock_quantity' => 20, 'description' => '16mm carbon fiber paddle with a forgiving sweet spot. A solid all-around pick.', 'image' => 'images/paddles/franklin.jpg'],
            ['was' => 'Control Paddle', 'name' => 'SLK Halo Control', 'brand' => 'Selkirk', 'hourly_rate' => 100, 'stock_quantity' => 10, 'description' => 'Control-focused paddle for soft dinks, drops and resets.', 'image' => 'images/paddles/selkirk.jpg'],
            ['was' => 'Power Paddle', 'name' => 'Magnus', 'brand' => 'JOOLA', 'hourly_rate' => 150, 'stock_quantity' => 6, 'description' => 'Carbon-face paddle with extra pop and spin for advanced players.', 'image' => 'images/paddles/joola.jpg'],
        ];

        foreach ($paddles as $data) {
            $paddle = Paddle::where('name', $data['name'])->first()
                ?? Paddle::where('name', $data['was'])->first()
                ?? new Paddle;

            // Replace the old generic sample photos; never touch a photo an admin uploaded.
            if (! $paddle->image_path || str_starts_with($paddle->image_path, 'images/paddles/')) {
                $paddle->image_path = $data['image'];
            }

            unset($data['was']);
            $paddle->fill(collect($data)->except('image')->all() + ['is_active' => true])->save();
        }
    }
}
