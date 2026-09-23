<?php

namespace App\Filament\Resources\StockTakes\Tables;

use App\Models\StockTake;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTakesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('name')->label('Sesi')->searchable()->weight('bold'),
            TextColumn::make('location_name')->label('Cakupan')->placeholder('Seluruh lokasi'),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => StockTake::STATUSES[$state] ?? $state),
            TextColumn::make('items_count')->label('Jumlah Aset')->counts('items'),
            TextColumn::make('created_at')->label('Dimulai')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('-'),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(StockTake::STATUSES),
        ])->recordActions([ViewAction::make()->label('Periksa')]);
    }
}
