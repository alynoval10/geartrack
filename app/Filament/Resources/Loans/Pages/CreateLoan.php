<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Services\LoanAssetEligibility;
use App\Services\LoanService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    /**
     * Tambahkan hasil scan tanpa menghapus perangkat yang telah dipilih sebelumnya.
     */
    #[On('loan-asset-scanned')]
    public function addScannedAsset(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = app(LoanAssetEligibility::class)->findByQrToken($token);

        if (! $asset) {
            $this->sendScanResult(false, 'Perangkat tidak tersedia, sedang dipinjam, rusak, atau dalam perawatan.');

            return;
        }

        $selectedAssetIds = collect($this->data['asset_ids'] ?? [])->map(fn ($id): int => (int) $id);

        if ($selectedAssetIds->contains($asset->id)) {
            $this->sendScanResult(false, $asset->asset_code.' sudah ada dalam pilihan.');

            return;
        }

        if ($selectedAssetIds->count() >= 100) {
            $this->sendScanResult(false, 'Maksimal 100 perangkat dalam satu peminjaman.');

            return;
        }

        $this->data['selection_type'] = 'asset';
        $this->data['asset_set_id'] = null;
        $this->data['asset_ids'] = [...$selectedAssetIds->all(), $asset->id];

        $this->sendScanResult(
            true,
            $asset->asset_code.' — '.$asset->name.' ditambahkan. Total '.count($this->data['asset_ids']).' perangkat.'
        );
    }

    private function sendScanResult(bool $success, string $message): void
    {
        $this->dispatch('loan-asset-scan-result', success: $success, message: $message);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(LoanService::class)->borrow($data, auth()->user());
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
