<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use App\Models\AssetTransferItem;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransferItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transferItems';

    protected static ?string $title = 'Riwayat Mutasi';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('assetTransfer.code')->label('Nomor'),
            TextColumn::make('source_location_name')->label('Dari')->placeholder('-'),
            TextColumn::make('assetTransfer.destination_location_name')->label('Ke'),
            TextColumn::make('assetTransfer.receiver_name')->label('Penerima'),
            TextColumn::make('assetTransfer.transferred_at')->label('Tanggal')->dateTime('d M Y H:i'),
        ])->recordActions([
            Action::make('viewTransfer')->label('Berita Acara')
                ->url(fn (AssetTransferItem $record): string => AssetTransferResource::getUrl('view', ['record' => $record->asset_transfer_id])),
        ]);
    }
}
