<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetTransferItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_transfer_id' => AssetTransfer::factory(), 'asset_id' => Asset::factory(),
            'asset_code' => fake()->unique()->uuid(), 'asset_name' => 'Komputer', 'condition' => 'good',
            'source_location_name' => 'Lab Asal',
        ];
    }
}
