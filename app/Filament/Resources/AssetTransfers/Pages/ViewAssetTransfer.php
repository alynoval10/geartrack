<?php

namespace App\Filament\Resources\AssetTransfers\Pages;

use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetTransfer extends ViewRecord
{
    protected static string $resource = AssetTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('document')->label('Cetak Berita Acara')
                ->url(fn (): string => route('transfers.document', ['transfer' => $this->record]))
                ->openUrlInNewTab(),
        ];
    }
}
