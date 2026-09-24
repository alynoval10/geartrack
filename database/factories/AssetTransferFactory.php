<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'MUT-'.fake()->unique()->uuid(),
            'destination_location_id' => LocationFactory::new(),
            'destination_location_name' => 'Lab Tujuan',
            'sender_name' => fake()->name(), 'receiver_name' => fake()->name(),
            'reason' => fake()->sentence(), 'transferred_at' => now(),
            'created_by' => User::factory(), 'created_by_name' => 'Petugas',
        ];
    }
}
