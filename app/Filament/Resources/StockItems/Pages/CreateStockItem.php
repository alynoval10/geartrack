<?php

namespace App\Filament\Resources\StockItems\Pages;

use App\Filament\Resources\StockItems\StockItemResource;
use App\Services\StockMovementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateStockItem extends CreateRecord
{
    protected static string $resource = StockItemResource::class;

    private ?string $initialStock = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialStock = (string) ($data['initial_stock'] ?? '0');
        unset($data['initial_stock']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ((float) $this->initialStock <= 0) {
            return;
        }

        $user = auth()->user();
        if (! $user) {
            throw ValidationException::withMessages(['initial_stock' => 'Sesi pengguna tidak ditemukan. Silakan masuk kembali.']);
        }

        app(StockMovementService::class)->record(
            $this->getRecord(),
            $user,
            'in',
            $this->initialStock,
            'Stok awal saat barang dibuat.',
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
