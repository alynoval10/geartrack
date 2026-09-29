<?php

namespace Database\Factories;

use App\Models\AssetDisposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDisposal>
 */
class AssetDisposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'HAP-'.fake()->unique()->numerify('########'),
            'reason_type' => 'major_damage',
            'reason' => 'Aset sudah tidak ekonomis untuk diperbaiki.',
            'disposal_date' => today(),
            'status' => 'pending',
            'submitted_by' => User::factory(),
            'submitted_by_name' => fake()->name(),
        ];
    }
}
