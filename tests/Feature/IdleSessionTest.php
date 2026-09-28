<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceIdleSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', EnforceIdleSession::class])
            ->get('/test/idle-session', fn (): string => 'aktif');
    }

    public function test_active_session_continues_and_records_user_version(): void
    {
        $user = User::factory()->create(['role' => 'guru', 'session_version' => 3]);

        $this->actingAs($user)
            ->withSession([EnforceIdleSession::LAST_ACTIVITY_KEY => now()->subMinutes(4)->timestamp])
            ->get('/test/idle-session')
            ->assertOk()
            ->assertSessionHas(EnforceIdleSession::USER_VERSION_KEY, 3);
    }

    public function test_session_logs_out_after_five_minutes_without_activity(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($user)
            ->withSession([
                EnforceIdleSession::LAST_ACTIVITY_KEY => now()->subMinutes(5)->timestamp,
                EnforceIdleSession::USER_VERSION_KEY => $user->session_version,
            ])
            ->get('/test/idle-session')
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHas('session_status');

        $this->assertGuest();
    }

    public function test_changed_user_version_revokes_the_old_session(): void
    {
        $user = User::factory()->create(['role' => 'guru', 'session_version' => 2]);

        $this->actingAs($user)
            ->withSession([
                EnforceIdleSession::LAST_ACTIVITY_KEY => now()->timestamp,
                EnforceIdleSession::USER_VERSION_KEY => 1,
            ])
            ->get('/test/idle-session')
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }
}
