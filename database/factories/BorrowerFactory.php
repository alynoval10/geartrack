<?php

namespace Database\Factories;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrower>
 */
class BorrowerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'staff',
            'name' => fake()->name(),
            'class_name' => null,
            'academic_year' => null,
            'contact' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function student(): static
    {
        return $this->state(fn (): array => [
            'type' => 'student',
            'class_name' => fake()->randomElement(['X TKJ 1', 'XI TKJ 1', 'XII TKJ 1']),
            'academic_year' => SchoolSetting::query()->value('academic_year') ?: '2026/2027',
            'contact' => null,
        ]);
    }
}
