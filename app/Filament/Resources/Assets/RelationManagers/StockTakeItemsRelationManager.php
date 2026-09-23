<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Models\StockTakeItem;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTakeItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockTakeItems';

    protected static ?string $title = 'Riwayat Stock Opname';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('stockTake.name')->label('Sesi'),
            TextColumn::make('result')->label('Hasil')->badge()
                ->formatStateUsing(fn (string $state): string => StockTakeItem::RESULTS[$state] ?? $state),
            TextColumn::make('expected_location_name')->label('Lokasi Awal')->placeholder('-'),
            TextColumn::make('observed_location_name')->label('Lokasi Aktual')->placeholder('-'),
            TextColumn::make('checked_at')->label('Diperiksa')->dateTime('d M Y H:i')->placeholder('Belum diperiksa'),
            TextColumn::make('checker.name')->label('Petugas')->placeholder('-'),
            TextColumn::make('notes')->label('Catatan')->wrap(),
        ])->recordActions([
            Action::make('session')->label('Lihat Sesi')
                ->url(fn (StockTakeItem $record): string => StockTakeResource::getUrl('view', ['record' => $record->stock_take_id])),
        ]);
    }
}
