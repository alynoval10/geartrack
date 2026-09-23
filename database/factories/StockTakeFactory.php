<?php

namespace Database\Factories;

use App\Models\StockTake;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTakeFactory extends Factory
{
    protected $model = StockTake::class;

    public function definition(): array
    {
        return ['name' => 'Stock Opname '.fake()->word(), 'status' => 'open', 'created_by' => UserFactory::new()];
    }
}
