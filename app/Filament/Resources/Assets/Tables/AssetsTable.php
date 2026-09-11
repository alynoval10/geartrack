<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Exports\AssetExporter;
use Filament\Actions\ExportAction;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('model')
                    ->label('Model / Tipe')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('serial_number')
                    ->label('Nomor Seri')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'good' => 'Baik',
                        'minor_damage' => 'Rusak Ringan',
                        'major_damage' => 'Rusak Berat',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'good' => 'success',
                        'minor_damage' => 'warning',
                        'major_damage' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'borrowed' => 'Dipinjam',
                        'maintenance' => 'Perawatan',
                        'retired' => 'Tidak Digunakan',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'available' => 'success',
                        'borrowed' => 'info',
                        'maintenance' => 'warning',
                        'retired' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('acquisition_date')
                    ->label('Tanggal Perolehan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('funding_source')
                    ->label('Sumber Dana')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchase_price')
                    ->label('Harga Perolehan')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

                        ->filters([

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('location')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'good' => 'Baik',
                        'minor_damage' => 'Rusak Ringan',
                        'major_damage' => 'Rusak Berat',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'borrowed' => 'Dipinjam',
                        'maintenance' => 'Perawatan',
                        'retired' => 'Tidak Digunakan',
                    ]),

            ])


            ->headerActions([
                ExportAction::make()
                    ->label('Export Aset')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(AssetExporter::class),
            ])



            ->recordActions([
                ActionGroup::make([

                    ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label('Edit Aset')
                        ->icon('heroicon-o-pencil-square'),

                    Action::make('qrLabel')
                        ->label('Label QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->url(
                            fn ($record): string => route(
                                'asset.qr.label',
                                [
                                    'token' => $record->qr_token,
                                ]
                            )
                        )
                        ->openUrlInNewTab(),

                ]),
            ])

            ->toolbarActions([
                BulkActionGroup::make([

                    BulkAction::make('printQrLabels')
                        ->label('Cetak Label QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->action(function (Collection $records, $livewire) {
                            $ids = $records
                                ->pluck('id')
                                ->implode(',');

                            $url = route('asset.qr.bulk-label', [
                                'assets' => $ids,
                            ]);

                            $livewire->js(
                                "window.open(" . json_encode($url) . ", '_blank')"
                            );
                        }),

                    DeleteBulkAction::make()
                        ->label('Hapus Aset')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),

                ])
                    ->label('Tindakan'),
            ]);
    }
}