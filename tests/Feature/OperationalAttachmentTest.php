<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetDisposals\Pages\CreateAssetDisposal;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\User;
use App\Services\AssetDisposalService;
use App\Services\AssetTransferService;
use App\Services\MaintenanceService;
use App\Services\StockTakeService;
use Database\Factories\LocationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalAttachmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_photo_can_be_uploaded_from_an_operational_form(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $asset = Asset::factory()->create();

        Livewire::test(CreateAssetDisposal::class)
            ->fillForm([
                'asset_ids' => [$asset->id],
                'reason_type' => 'major_damage',
                'reason' => 'Kerusakan tidak ekonomis untuk diperbaiki.',
                'disposal_date' => today()->format('Y-m-d'),
                'attachments' => [UploadedFile::fake()->create('kondisi.jpg', 100, 'image/jpeg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $attachment = AssetDisposal::query()->sole()->attachments[0];
        Storage::disk('public')->assertExists($attachment);
        $this->assertStringStartsWith('evidence/asset-disposals/', $attachment);
    }

    public function test_evidence_paths_are_saved_for_disposals_and_transfers(): void
    {
        $user = User::factory()->create();
        $disposalAsset = Asset::factory()->create();
        $disposal = app(AssetDisposalService::class)->submit([
            'asset_ids' => [$disposalAsset->id],
            'reason_type' => 'major_damage',
            'reason' => 'Kerusakan tidak ekonomis untuk diperbaiki.',
            'disposal_date' => today()->format('Y-m-d'),
            'attachments' => ['evidence/asset-disposals/kondisi.jpg'],
        ], $user);

        $transferAsset = Asset::factory()->create();
        $destination = LocationFactory::new()->create();
        $transfer = app(AssetTransferService::class)->transfer([
            'selection_type' => 'asset',
            'asset_ids' => [$transferAsset->id],
            'destination_location_id' => $destination->id,
            'sender_name' => 'Petugas Lama',
            'receiver_user_id' => $user->id,
            'receiver_name' => $user->name,
            'reason' => 'Pemindahan aset ke ruang baru.',
            'attachments' => ['evidence/asset-transfers/serah-terima.pdf'],
        ], $user);

        $this->assertSame(['evidence/asset-disposals/kondisi.jpg'], $disposal->attachments);
        $this->assertSame(['evidence/asset-transfers/serah-terima.pdf'], $transfer->attachments);
    }

    public function test_evidence_paths_are_saved_for_maintenance_report_and_actions(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();
        $service = app(MaintenanceService::class);
        $report = $service->report($asset, [
            'title' => 'Perangkat tidak menyala',
            'type' => 'damage',
            'description' => 'Lampu daya tidak menyala.',
            'reported_condition' => 'minor_damage',
            'attachments' => ['evidence/maintenance-reports/sebelum.jpg'],
        ], $user);

        $service->update($report, 'started', [
            'technician' => 'Teknisi Sekolah',
            'notes' => 'Pemeriksaan awal dilakukan.',
            'attachments' => ['evidence/maintenance-actions/diagnosis.pdf'],
        ], $user);

        $this->assertSame(['evidence/maintenance-reports/sebelum.jpg'], $report->attachments);
        $this->assertSame(
            ['evidence/maintenance-actions/diagnosis.pdf'],
            $report->entries()->where('action', 'started')->sole()->attachments,
        );
    }

    public function test_stock_take_evidence_is_saved_and_preserved_when_not_replaced(): void
    {
        $user = User::factory()->create();
        Asset::factory()->create();
        $service = app(StockTakeService::class);
        $stockTake = $service->start(['name' => 'Opname Bukti'], $user);
        $item = $stockTake->items()->sole();

        $service->record($stockTake, $item, [
            'result' => 'found',
            'attachments' => ['evidence/stock-takes/ditemukan.webp'],
        ], $user);
        $service->record($stockTake, $item, [
            'result' => 'found',
            'notes' => 'Pemeriksaan ulang.',
        ], $user);

        $this->assertSame(['evidence/stock-takes/ditemukan.webp'], $item->refresh()->attachments);
    }

    public function test_unsafe_attachment_extension_is_rejected(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        try {
            app(MaintenanceService::class)->report($asset, [
                'title' => 'Perangkat tidak menyala',
                'type' => 'damage',
                'description' => 'Perlu diperiksa.',
                'reported_condition' => 'minor_damage',
                'attachments' => ['evidence/maintenance-reports/script.php'],
            ], $user);
            $this->fail('Lampiran dengan ekstensi yang tidak diizinkan harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attachments.0', $exception->errors());
        }

        $this->assertDatabaseCount('maintenance_reports', 0);
    }
}
