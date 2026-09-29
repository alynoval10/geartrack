<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetDisposalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDisposalItem>
 */
class AssetDisposalItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_disposal_id' => AssetDisposal::factory(),
            'asset_id' => Asset::factory(),
            'asset_code' => fake()->unique()->bothify('GT-???-####'),
            'asset_name' => fake()->words(3, true),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'condition' => 'major_damage',
            'previous_status' => 'available',
            'location_name' => fake()->word(),
            'custodian_name' => fake()->name(),
        ];
    }
}
