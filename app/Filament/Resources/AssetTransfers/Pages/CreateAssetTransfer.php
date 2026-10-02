<?php

namespace App\Filament\Resources\AssetTransfers\Pages;

use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use App\Models\Asset;
use App\Services\AssetTransferService;
use App\Services\TransferAssetEligibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateAssetTransfer extends CreateRecord
{
    protected static string $resource = AssetTransferResource::class;

    public function mount(): void
    {
        parent::mount();

        $assetId = request()->integer('asset');
        if ($assetId < 1) {
            return;
        }

        $asset = Asset::find($assetId);
        if (! $asset) {
            Notification::make()->title('Aset tidak ditemukan')->body('Periksa kembali QR atau pilih perangkat secara manual.')->warning()->send();

            return;
        }

        $reason = app(TransferAssetEligibility::class)->ineligibilityReason($asset);
        if ($reason !== null) {
            Notification::make()->title('Aset tidak dapat dimutasi')->body($reason)->warning()->send();

            return;
        }

        // Pertahankan nilai default penerima dan penyerah sambil memilih aset hasil scan.
        $this->data['selection_type'] = 'asset';
        $this->data['asset_set_id'] = null;
        $this->data['asset_ids'] = [$asset->id];
    }

    /**
     * Tambahkan perangkat satuan dari QR dan pertahankan pilihan manual yang sudah ada.
     */
    #[On('transfer-asset-scanned')]
    public function addScannedAsset(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = app(TransferAssetEligibility::class)->findByQrToken($token);

        if (! $asset) {
            $this->sendScanResult(false, 'Perangkat tidak dapat dimutasi satuan karena sedang dipinjam, hilang, atau menjadi anggota paket.');

            return;
        }

        $selectedAssetIds = collect($this->data['asset_ids'] ?? [])->map(fn ($id): int => (int) $id);

        if ($selectedAssetIds->contains($asset->id)) {
            $this->sendScanResult(false, $asset->asset_code.' sudah ada dalam pilihan.');

            return;
        }

        if ($selectedAssetIds->count() >= 100) {
            $this->sendScanResult(false, 'Maksimal 100 perangkat dalam satu mutasi.');

            return;
        }

        $this->data['selection_type'] = 'asset';
        $this->data['asset_set_id'] = null;
        $this->data['asset_ids'] = [...$selectedAssetIds->all(), $asset->id];

        $this->sendScanResult(true, $asset->asset_code.' — '.$asset->name.' ditambahkan.');

        // Kesalahan tetap mempertahankan modal, sedangkan hasil valid langsung kembali ke formulir mutasi.
        $this->unmountAction();
    }

    private function sendScanResult(bool $success, string $message): void
    {
        $this->dispatch('transfer-asset-scan-result', success: $success, message: $message);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AssetTransferService::class)->transfer($data, auth()->user());
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
