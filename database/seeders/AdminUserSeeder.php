<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Ensure the application always has an active administrator account.
     */
    public function run(): void
    {
        $administrator = User::withTrashed()->firstOrNew([
            'email' => 'admin@geartrack.local',
        ]);

        if (! $administrator->exists) {
            $administrator->fill([
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'must_change_password' => true,
            ]);
        }

        $administrator->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'deleted_at' => null,
        ])->save();
    }
}
