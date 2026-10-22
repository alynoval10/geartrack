<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state, Location $record): string => $record->fullName())
                    ->weight('bold'),

                TextColumn::make('parent.name')
                    ->label('Lokasi Induk')
                    ->placeholder('Lokasi utama')
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->placeholder('-'),

                TextColumn::make('assets_count')
                    ->label('Jumlah Aset')
                    ->state(fn (Location $record): int => (int) $record->assets_count + (int) $record->child_assets_count)
                    ->tooltip(fn (Location $record): string => $record->parent_id === null
                        ? 'Lokasi ini: '.$record->assets_count.'; sub-lokasi: '.$record->child_assets_count
                        : 'Jumlah aset di sub-lokasi ini: '.$record->assets_count)
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('openMap')
                    ->label('Lihat Peta')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->url(fn (Location $record): ?string => $record->mapUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Location $record): bool => $record->mapUrl() !== null),
                Action::make('printLabel')
                    ->label('Cetak Label A4')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Location $record): string => route('locations.label', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('Belum ada lokasi')
            ->emptyStateDescription('Tambahkan ruang, laboratorium, atau tempat penyimpanan sebelum menempatkan aset.');
    }
}
