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
            'nis' => null,
            'class_level' => null,
            'class_name' => null,
            'academic_year' => null,
            'contact' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function student(): static
    {
        return $this->state(function (): array {
            $classLevel = fake()->randomElement(['X', 'XI', 'XII']);

            return [
                'type' => 'student',
                'nis' => fake()->unique()->numerify('##########'),
                'class_level' => $classLevel,
                'class_name' => fake()->randomElement(['TJKT 1', 'TJKT 2']),
                'academic_year' => SchoolSetting::query()->value('academic_year') ?: '2026/2027',
                'contact' => null,
            ];
        });
    }
}
