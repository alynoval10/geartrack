<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'asset_code' => 'PC-'.fake()->unique()->numerify('######'),
            'name' => 'PC '.fake()->word(),
            'category_id' => CategoryFactory::new(),
            'condition' => 'good',
            'status' => 'available',
        ];
    }
}
