<?php

namespace Tests\Feature;

use App\Filament\Pages\Login;
use App\Http\Middleware\RequirePasswordChange;
use App\Models\User;
use App\Services\UserManagementService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create(['password' => 'password-lama', 'must_change_password' => true]);

        $this->actingAs($user)->post(route('password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('filament.admin.pages.change-password'));

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_forced_user_is_redirected_until_password_is_changed(): void
    {
        Route::middleware(['web', 'auth', RequirePasswordChange::class])
            ->get('/test/protected-menu', fn (): string => 'menu');
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/test/protected-menu')
            ->assertRedirect(route('filament.admin.pages.change-password'));
    }

    public function test_login_ignores_a_stale_password_change_destination_for_a_regular_user(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        User::factory()->create([
            'email' => 'guru@example.com',
            'password' => 'password-guru',
            'must_change_password' => false,
        ]);

        $this->withSession([
            'url.intended' => route('filament.admin.pages.change-password'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'guru@example.com',
                'password' => 'password-guru',
            ])
            ->call('authenticate')
            ->assertRedirect(Filament::getUrl());
    }

    public function test_admin_reset_can_require_a_password_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $guru = User::factory()->create(['role' => 'guru', 'must_change_password' => false]);
        $this->actingAs($admin);

        app(UserManagementService::class)->save([
            'name' => $guru->name,
            'email' => $guru->email,
            'role' => 'guru',
            'is_active' => true,
            'must_change_password' => true,
            'password' => 'reset-admin',
            'password_confirmation' => 'reset-admin',
        ], $guru);

        $this->assertTrue($guru->fresh()->must_change_password);
        $this->assertTrue(Hash::check('reset-admin', $guru->fresh()->password));
    }
}
