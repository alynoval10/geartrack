<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(), 'asset_id' => Asset::factory(),
            'asset_code' => fake()->unique()->uuid(), 'asset_name' => 'Komputer',
            'condition_before' => 'good', 'previous_status' => 'available',
        ];
    }
}
