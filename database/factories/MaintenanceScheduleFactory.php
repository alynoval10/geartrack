<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(), 'asset_code' => fake()->unique()->uuid(), 'asset_name' => 'Komputer',
            'title' => 'Pembersihan rutin', 'due_date' => today()->addDays(7),
            'interval_days' => 30, 'is_active' => true, 'created_by' => User::factory(),
        ];
    }
}
