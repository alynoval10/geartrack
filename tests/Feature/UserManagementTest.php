<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserManagementService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_admin_can_create_a_guru_through_management_service(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $guru = app(UserManagementService::class)->save([
            'name' => 'Guru Inventaris',
            'email' => 'GURU@EXAMPLE.COM',
            'role' => 'guru',
            'is_active' => true,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $this->assertSame('guru@example.com', $guru->email);
        $this->assertSame('guru', $guru->role);
        $this->assertTrue($guru->is_active);
    }

    public function test_admin_cannot_demote_or_disable_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->expectException(ValidationException::class);

        app(UserManagementService::class)->save([
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'guru',
            'is_active' => false,
        ], $admin);
    }

    public function test_role_change_revokes_existing_login_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru', 'session_version' => 1]);
        $this->actingAs($admin);

        app(UserManagementService::class)->save([
            'name' => $guru->name,
            'email' => $guru->email,
            'role' => 'admin',
            'is_active' => true,
        ], $guru);

        $this->assertSame(2, $guru->fresh()->session_version);
    }
}
