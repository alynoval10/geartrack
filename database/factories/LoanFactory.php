<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PJM-'.fake()->unique()->uuid(),
            'borrower_name' => fake()->name(), 'responsible_name' => fake()->name(),
            'purpose' => fake()->sentence(), 'borrowed_at' => now(),
            'due_date' => today()->addDays(7), 'status' => 'open', 'created_by' => User::factory(),
        ];
    }
}
