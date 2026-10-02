<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\Loans\Pages\CreateLoan;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Models\Asset;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UiFeedbackTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_empty_operational_pages_explain_how_to_start(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(AssetResource::getUrl('index'))
            ->assertSee('Belum ada aset')
            ->assertSee('Impor Aset');

        $this->get(LoanResource::getUrl('index'))
            ->assertSee('Belum ada peminjaman')
            ->assertSee('scan QR perangkat');

        $this->get(MaintenanceReportResource::getUrl('index'))
            ->assertSee('Belum ada laporan kerusakan atau perawatan');
    }

    public function test_unknown_qr_displays_a_recovery_message(): void
    {
        config(['app.debug' => false]);

        $this->get(route('asset.qr.show', 'qr-tidak-terdaftar'))
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Scan QR Lain');
    }

    public function test_invalid_prefilled_asset_explains_why_it_cannot_be_used(): void
    {
        $this->actingAs(User::factory()->create());
        $asset = Asset::factory()->create(['status' => 'lost', 'condition' => 'good']);

        Livewire::withQueryParams(['asset' => $asset->id])
            ->test(CreateLoan::class)
            ->assertNotified('Aset tidak dapat dipinjam')
            ->assertSet('data.asset_ids', []);
    }
}
