<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\MaintenanceReports\Pages\CreateMaintenanceReport;
use App\Filament\Resources\MaintenanceReports\Pages\ViewMaintenanceReport;
use App\Filament\Resources\MaintenanceReports\RelationManagers\EntriesRelationManager;
use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Services\StockTakeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.env' => 'local']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function reportData(): array
    {
        return [
            'title' => 'PC tidak menyala', 'type' => 'damage',
            'reported_condition' => 'minor_damage', 'description' => 'Lampu daya mati.',
        ];
    }

    public function test_creates_damage_report_from_asset_with_initial_history(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();

        Livewire::test(ViewAsset::class, ['record' => $asset->id])
            ->callAction('reportMaintenance', data: $this->reportData())->assertHasNoActionErrors();

        $report = MaintenanceReport::sole();
        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'asset_id' => $asset->id, 'reported_by' => $user->id, 'status' => 'open']);
        $this->assertDatabaseHas('maintenance_entries', ['maintenance_report_id' => $report->id, 'action' => 'reported', 'user_id' => $user->id]);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'condition' => 'minor_damage', 'status' => 'available']);
    }

    public function test_routine_maintenance_can_be_created_from_menu(): void
    {
        $this->operator();
        $asset = Asset::factory()->create();

        Livewire::test(CreateMaintenanceReport::class)->fillForm([
            'asset_id' => $asset->id, 'title' => 'Bersihkan kipas', 'type' => 'maintenance',
            'reported_condition' => 'good', 'description' => 'Pembersihan rutin semester.',
        ])->call('create')->assertHasNoFormErrors();

        $this->assertDatabaseHas('maintenance_reports', ['asset_id' => $asset->id, 'type' => 'maintenance']);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'condition' => 'good']);
    }

    public function test_damage_report_cannot_claim_good_condition(): void
    {
        $this->operator();
        $asset = Asset::factory()->create();

        Livewire::test(CreateMaintenanceReport::class)->fillForm([
            ...$this->reportData(), 'asset_id' => $asset->id, 'reported_condition' => 'good',
        ])->call('create')->assertHasFormErrors(['reported_condition'])
            ->assertSee('Pilih tingkat kerusakan untuk laporan kerusakan.');

        $this->assertDatabaseCount('maintenance_reports', 0);
    }

    public function test_rejects_duplicate_active_report(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        app(MaintenanceService::class)->report($asset, $this->reportData(), $user);

        Livewire::test(ViewAsset::class, ['record' => $asset->id])
            ->callAction('reportMaintenance', data: $this->reportData())->assertNotified('Belum dapat disimpan');

        $this->assertDatabaseCount('maintenance_reports', 1);
        $this->assertDatabaseCount('maintenance_entries', 1);
    }

    public function test_starts_maintenance_and_records_technician(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create(['status' => 'in_use']);
        $report = app(MaintenanceService::class)->report($asset, $this->reportData(), $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('started', data: ['technician' => 'Teknisi Lab', 'notes' => 'Periksa catu daya'])
            ->assertHasNoActionErrors()->assertNotified('Berhasil disimpan');

        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'in_progress', 'technician' => 'Teknisi Lab', 'previous_asset_status' => 'in_use']);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'maintenance']);
        $this->get(route('asset.qr.show', $asset->qr_token))->assertSee('Dalam Perawatan');
    }

    public function test_notes_and_resolution_keep_all_history_and_sum_costs(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);
        $service->update($report, 'started', ['technician' => 'Teknisi', 'notes' => 'Diagnosis'], $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('note', data: ['notes' => 'Ganti power supply', 'cost' => 250000])
            ->assertNotified('Berhasil disimpan');
        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('resolved', data: ['notes' => 'Uji menyala normal', 'condition' => 'good', 'asset_status' => 'available', 'cost' => 50000])
            ->assertNotified('Berhasil disimpan');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'condition' => 'good', 'status' => 'available']);
        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'resolved']);
        $this->assertSame(4, $report->entries()->count());
        $this->assertSame(300000.0, (float) $report->entries()->sum('cost'));
        $this->assertNotNull($report->fresh()->closed_at);
    }

    public function test_cancellation_restores_pre_maintenance_status(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create(['status' => 'in_use']);
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);
        $service->update($report, 'started', ['technician' => 'Teknisi', 'notes' => 'Diagnosis'], $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('cancelled', data: ['notes' => 'Dialihkan ke vendor'])
            ->assertNotified('Berhasil disimpan');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'in_use', 'condition' => 'minor_damage']);
        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'cancelled']);
    }

    public function test_closed_report_rejects_late_note(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);
        $service->update($report, 'cancelled', ['notes' => 'Laporan keliru'], $user);

        try {
            $service->update($report, 'note', ['notes' => 'Perubahan terlambat'], $user);
            $this->fail('Laporan tertutup tidak boleh berubah.');
        } catch (ValidationException $exception) {
            $this->assertSame('Laporan sudah ditutup. Buat laporan baru untuk kejadian berikutnya.', $exception->errors()['notes'][0]);
        }

        $this->assertSame(2, $report->entries()->count());
    }

    public function test_borrowed_asset_cannot_be_put_into_maintenance(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create(['status' => 'borrowed']);
        $report = app(MaintenanceService::class)->report($asset, $this->reportData(), $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('started', data: ['technician' => 'Teknisi', 'notes' => 'Diagnosis'])
            ->assertNotified('Belum dapat disimpan');

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'borrowed']);
        $this->assertSame(1, $report->entries()->count());
    }

    public function test_resolution_rejects_major_damage_marked_available(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);
        $service->update($report, 'started', ['technician' => 'Teknisi', 'notes' => 'Diagnosis'], $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('resolved', data: ['notes' => 'Masih rusak', 'condition' => 'major_damage', 'asset_status' => 'available'])
            ->assertNotified('Belum dapat disimpan');

        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'maintenance']);
    }

    public function test_negative_cost_is_rejected_without_adding_entry(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $report = app(MaintenanceService::class)->report($asset, $this->reportData(), $user);

        Livewire::test(ViewMaintenanceReport::class, ['record' => $report->id])
            ->callAction('note', data: ['notes' => 'Biaya salah', 'cost' => -100])
            ->assertHasActionErrors(['cost' => 'min']);

        $this->assertSame(1, $report->entries()->count());
    }

    public function test_history_survives_asset_deletion(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $report = app(MaintenanceService::class)->report($asset, $this->reportData(), $user);

        $asset->delete();

        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'asset_id' => null, 'asset_code' => $asset->asset_code]);
        $this->assertSame(1, $report->entries()->count());
    }

    public function test_public_qr_does_not_disclose_internal_report_notes(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        app(MaintenanceService::class)->report($asset, [...$this->reportData(), 'description' => 'Catatan internal rahasia'], $user);

        $this->get(route('asset.qr.show', $asset->qr_token))
            ->assertSee('Laporkan Kerusakan / Perawatan')->assertDontSee('Catatan internal rahasia');
    }

    public function test_management_requires_login(): void
    {
        $this->get(MaintenanceReportResource::getUrl('index'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_detail_and_entries_render_escaped_notes(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $report = app(MaintenanceService::class)->report($asset, [...$this->reportData(), 'description' => '<script>alert(1)</script>'], $user);

        $this->get(MaintenanceReportResource::getUrl('view', ['record' => $report]))
            ->assertSee('PC tidak menyala')->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
        Livewire::test(EntriesRelationManager::class, ['ownerRecord' => $report, 'pageClass' => ViewMaintenanceReport::class])
            ->assertCanSeeTableRecords($report->entries);
    }

    public function test_recovered_asset_returns_to_active_maintenance(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $maintenance = app(MaintenanceService::class);
        $report = $maintenance->report($asset, $this->reportData(), $user);
        $maintenance->update($report, 'started', ['technician' => 'Teknisi', 'notes' => 'Diagnosis'], $user);
        $stockTake = app(StockTakeService::class);
        $session = $stockTake->start(['name' => 'Sesi'], $user);
        $item = $session->items()->sole();
        $stockTake->record($session, $item, ['result' => 'missing', 'notes' => 'Tidak di meja'], $user);

        $stockTake->record($session, $item, ['result' => 'found'], $user);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'maintenance']);
        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'in_progress']);
    }

    public function test_report_deep_link_opens_report_form(): void
    {
        $this->operator();
        $asset = Asset::factory()->create();

        Livewire::withQueryParams(['action' => 'reportMaintenance'])
            ->test(ViewAsset::class, ['record' => $asset->id])
            ->assertSet('defaultAction', 'reportMaintenance')
            ->call('mountAction', 'reportMaintenance', [], ['mountedFromUrl' => true])
            ->assertSet('mountedActions.0.name', 'reportMaintenance');
    }

    public function test_resolving_without_starting_is_rejected(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);

        try {
            $service->update($report, 'resolved', ['notes' => 'Belum ditangani', 'condition' => 'good', 'asset_status' => 'available'], $user);
            $this->fail('Penyelesaian sebelum penanganan harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame('Mulai penanganan sebelum menyelesaikan laporan.', $exception->errors()['notes'][0]);
        }

        $this->assertSame(1, $report->entries()->count());
    }

    public function test_major_damage_can_be_closed_as_retired(): void
    {
        $user = $this->operator();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, $this->reportData(), $user);
        $service->update($report, 'started', ['technician' => 'Teknisi', 'notes' => 'Diagnosis'], $user);

        $service->update($report, 'resolved', ['notes' => 'Tidak dapat diperbaiki', 'condition' => 'major_damage', 'asset_status' => 'retired'], $user);

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'condition' => 'major_damage', 'status' => 'retired']);
        $this->assertDatabaseHas('maintenance_reports', ['id' => $report->id, 'status' => 'resolved']);
    }
}
