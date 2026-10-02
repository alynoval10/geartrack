<?php

namespace App\Filament\Resources\MaintenanceReports\Pages;

use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Models\Asset;
use App\Services\MaintenanceAssetEligibility;
use App\Services\MaintenanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateMaintenanceReport extends CreateRecord
{
    protected static string $resource = MaintenanceReportResource::class;

    public function mount(): void
    {
        parent::mount();

        $assetId = request()->integer('asset');
        if ($assetId < 1) {
            return;
        }

        $asset = app(MaintenanceAssetEligibility::class)->query()->find($assetId);
        if ($asset) {
            $this->data['asset_id'] = $asset->id;
        }
    }

    /**
     * Pilih aset laporan dari QR tanpa melewati pemeriksaan laporan aktif.
     */
    #[On('maintenance-asset-scanned')]
    public function selectScannedAsset(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = app(MaintenanceAssetEligibility::class)->findByQrToken($token);

        if (! $asset) {
            $this->sendScanResult(false, 'Aset tidak ditemukan atau masih memiliki laporan kerusakan/perawatan aktif.');

            return;
        }

        if ((int) ($this->data['asset_id'] ?? 0) === $asset->id) {
            $this->sendScanResult(false, $asset->asset_code.' sudah dipilih.');

            return;
        }

        $this->data['asset_id'] = $asset->id;
        $this->sendScanResult(true, $asset->asset_code.' — '.$asset->name.' dipilih.');

        // Hasil valid langsung kembali ke formulir; kesalahan tetap terlihat di modal pemindai.
        $this->unmountAction();
    }

    private function sendScanResult(bool $success, string $message): void
    {
        $this->dispatch('maintenance-asset-scan-result', success: $success, message: $message);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(MaintenanceService::class)->report(Asset::findOrFail($data['asset_id']), $data, auth()->user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($errors, $field) => ['data.'.$field => $errors])->all()
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
