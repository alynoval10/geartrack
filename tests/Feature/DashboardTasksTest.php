<?php

namespace Tests\Feature;

use App\Filament\Widgets\UserTasks;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_sees_only_assets_assigned_to_them(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $other = User::factory()->create(['role' => 'guru']);
        Asset::factory()->create(['name' => 'PC Milik Guru', 'custodian_user_id' => $guru->id]);
        Asset::factory()->create(['name' => 'PC Guru Lain', 'custodian_user_id' => $other->id]);
        $this->actingAs($guru);

        Livewire::test(UserTasks::class)
            ->assertSee('Tugas Saya')
            ->assertSee('PC Milik Guru')
            ->assertDontSee('PC Guru Lain');
    }

    public function test_admin_sees_global_summary_and_filling_timeline(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Asset::factory()->count(2)->create();

        Livewire::test(UserTasks::class)
            ->assertSee('Ringkasan Operasional')
            ->assertSee('Timeline Pengisian GearTrack')
            ->assertSee('Masukkan inventaris');
    }

    public function test_dashboard_scan_button_opens_qr_scanner(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guru']));

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('qr.scan').'"', false)
            ->assertDontSee('Segera');
    }
}
