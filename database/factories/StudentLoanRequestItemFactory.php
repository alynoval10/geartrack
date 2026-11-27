<?php

namespace Database\Factories;

use App\Models\StudentLoanRequest;
use App\Models\StudentLoanRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentLoanRequestItem>
 */
class StudentLoanRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_loan_request_id' => StudentLoanRequest::factory(),
            'asset_id' => null,
            'asset_code' => 'GT-'.fake()->unique()->bothify('???-####'),
            'asset_name' => fake()->words(3, true),
        ];
    }
}
