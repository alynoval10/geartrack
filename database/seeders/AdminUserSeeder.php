<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = User::withTrashed()->firstOrNew([
            'email' => 'admin@geartrack.local',
        ]);
        $isNewAdministrator = ! $administrator->exists;

        if ($isNewAdministrator) {
            $administrator->fill([
                'name' => 'Administrator',
                'password' => 'admin123',
            ]);
        }

        $usesDefaultPassword = $isNewAdministrator || Hash::check('admin123', (string) $administrator->password);

        $administrator->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => $administrator->must_change_password || $usesDefaultPassword,
            'deleted_at' => null,
        ])->save();
    }
}
