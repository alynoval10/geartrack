<?php

namespace App\Filament\Resources\AssetTransfers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('code')->label('Nomor')->searchable(),
            TextColumn::make('transferred_at')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('destination_location_name')->label('Tujuan')->searchable(),
            TextColumn::make('receiver_name')->label('Penerima')->searchable(),
            TextColumn::make('items_count')->label('Perangkat')->counts('items'),
        ])->recordActions([ViewAction::make()])->toolbarActions([]);
    }
}
