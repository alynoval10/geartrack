<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class BorrowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        Borrower::query()->firstOrCreate(
            ['type' => 'staff', 'name' => 'Guru Demo'],
            ['contact' => 'guru.demo@example.test', 'is_active' => true],
        );

        Borrower::query()->updateOrCreate(
            [
                'type' => 'student',
                'class_level' => 'X',
                'name' => 'Siswa Demo',
                'academic_year' => SchoolSetting::query()->value('academic_year') ?: '2026/2027',
            ],
            ['nis' => '2026000001', 'class_name' => 'TJKT 1', 'is_active' => true],
        );
    }
}
