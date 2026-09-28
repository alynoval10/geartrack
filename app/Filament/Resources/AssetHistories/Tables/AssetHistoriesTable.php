<?php

namespace App\Filament\Resources\AssetHistories\Tables;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetHistory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['asset', 'user']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label('Pengguna')
                    ->getStateUsing(fn (AssetHistory $record): string => $record->user_name ?: 'Sistem')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Aktivitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AssetHistory::ACTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'deleted' => 'danger',
                        'transfer', 'stock_take' => 'info',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn (AssetHistory $record): ?string => $record->asset
                        ? AssetResource::getUrl('view', ['record' => $record->asset])
                        : null),

                TextColumn::make('asset_name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('old_value')
                    ->label('Sebelum')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('new_value')
                    ->label('Sesudah')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aktivitas')
                    ->options(AssetHistory::ACTIONS),

                SelectFilter::make('user')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
