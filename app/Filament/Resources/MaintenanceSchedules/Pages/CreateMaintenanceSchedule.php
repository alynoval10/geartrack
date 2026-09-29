<?php

namespace App\Filament\Resources\MaintenanceSchedules\Pages;

use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Models\Asset;
use App\Services\MaintenanceScheduleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateMaintenanceSchedule extends CreateRecord
{
    protected static string $resource = MaintenanceScheduleResource::class;

    /**
     * Pilih perangkat jadwal langsung dari label QR GearTrack.
     */
    #[On('schedule-asset-scanned')]
    public function selectScannedAsset(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = Asset::query()->where('qr_token', $token)->first();

        if (! $asset) {
            $this->sendScanResult(false, 'Perangkat tidak ditemukan.');

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
        $this->dispatch('schedule-asset-scan-result', success: $success, message: $message);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(MaintenanceScheduleService::class)->save($data, auth()->user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($errors, $field) => ['data.'.$field => $errors])->all(),
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
