<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_guru_can_access_the_panel(): void
    {
        $panel = Filament::getPanel('admin');
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertTrue($guru->canAccessPanel($panel));
        $this->assertSame('guru', (new User)->role);
    }
}
