<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_administrator_account(): void
    {
        $this->seed(AdminUserSeeder::class);

        $administrator = User::where('email', 'admin@geartrack.local')->firstOrFail();

        $this->assertSame('admin', $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertFalse($administrator->must_change_password);
        $this->assertTrue(Hash::check('admin123', $administrator->password));
    }

    public function test_it_repairs_access_without_resetting_an_existing_password(): void
    {
        $administrator = User::factory()->create([
            'email' => 'admin@geartrack.local',
            'password' => 'kata-sandi-lama',
            'role' => 'guru',
            'is_active' => false,
        ]);

        $this->seed(AdminUserSeeder::class);

        $administrator->refresh();

        $this->assertSame('admin', $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check('kata-sandi-lama', $administrator->password));
    }
}
