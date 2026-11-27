<?php

namespace Database\Factories;

use App\Models\StudentLoanRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentLoanRequest>
 */
class StudentLoanRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_account_id' => StudentAccount::factory(),
            'borrower_id' => null,
            'loan_id' => null,
            'reviewed_by' => null,
            'borrower_name' => fake()->name(),
            'borrower_nis' => fake()->numerify('##########'),
            'borrower_class' => fake()->randomElement(['X TJKT 1', 'XI TJKT 2', 'XII TJKT 3']),
            'academic_year' => '2026/2027',
            'purpose' => fake()->sentence(),
            'due_date' => today()->addDays(7),
            'status' => 'pending',
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }
}
