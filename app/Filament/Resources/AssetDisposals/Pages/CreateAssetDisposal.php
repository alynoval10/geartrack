<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Services\AssetDisposalEligibility;
use App\Services\AssetDisposalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateAssetDisposal extends CreateRecord
{
    protected static string $resource = AssetDisposalResource::class;

    #[On('asset-disposal-asset-scanned')]
    public function selectScannedAsset(string $token, AssetDisposalEligibility $eligibility): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = $eligibility->findEligibleByQrToken($token);

        if (! $asset) {
            $this->sendScanResult(false, 'Aset tidak ditemukan atau belum memenuhi syarat untuk diajukan penghapusannya.');

            return;
        }

        $assetIds = array_map('intval', $this->data['asset_ids'] ?? []);

        if (in_array($asset->id, $assetIds, true)) {
            $this->sendScanResult(false, $asset->asset_code.' sudah ada dalam daftar pengajuan.');

            return;
        }

        if (count($assetIds) >= 100) {
            $this->sendScanResult(false, 'Daftar pengajuan sudah mencapai batas 100 aset.');

            return;
        }

        $this->data['asset_ids'] = [...$assetIds, $asset->id];
        $this->sendScanResult(true, $asset->asset_code.' — '.$asset->name.' ditambahkan ke daftar pengajuan.');
        $this->unmountAction();
    }

    private function sendScanResult(bool $success, string $message): void
    {
        $this->dispatch('asset-disposal-scan-result', success: $success, message: $message);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AssetDisposalService::class)->submit($data, auth()->user());
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
