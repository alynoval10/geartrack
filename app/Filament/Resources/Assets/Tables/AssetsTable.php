<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Filament\Exports\AssetExporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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

                    /*
                    |--------------------------------------------------------------------------
                    | Cetak Label QR
                    |--------------------------------------------------------------------------
                    */
                    Action::make('qrLabel')
                        ->label('Cetak Label QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->url(
                            fn ($record): string => route(
                                'asset.qr.label',
                                [
                                    'token' => $record->qr_token,
                                    'paper' => 'label',
                                ]
                            )
                        )
                        ->openUrlInNewTab(),

                    /*
                    |--------------------------------------------------------------------------
                    | Cetak NIIMBOT
                    |--------------------------------------------------------------------------
                    */
                    Action::make('niimbot')
                        ->label('Cetak NIIMBOT')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(
                            fn ($record): string => route(
                                'asset.qr.niimbot',
                                [
                                    'token' => $record->qr_token,
                                ]
                            )
                        )
                        ->openUrlInNewTab(),

                    /*
                    |--------------------------------------------------------------------------
                    | Cetak Label QR A4
                    |--------------------------------------------------------------------------
                    */
                    Action::make('qrLabelA4')
                        ->label('Cetak Label QR A4')
                        ->icon('heroicon-o-document')
                        ->color('gray')
                        ->url(
                            fn ($record): string => route(
                                'asset.qr.label',
                                [
                                    'token' => $record->qr_token,
                                    'paper' => 'a4',
                                ]
                            )
                        )
                        ->openUrlInNewTab(),

                ]),
            ])

            ->toolbarActions([
                BulkActionGroup::make([

                    /*
                    |--------------------------------------------------------------------------
                    | Bulk Cetak Label QR
                    |--------------------------------------------------------------------------
                    */
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
                                'paper' => 'label',
                            ]);

                            $livewire->js(
                                'window.open(' . json_encode($url) . ", '_blank')"
                            );
                        }),

                    /*
                    |--------------------------------------------------------------------------
                    | Bulk Cetak Label QR A4
                    |--------------------------------------------------------------------------
                    */
                    BulkAction::make('printQrLabelsA4')
                        ->label('Cetak Label QR A4')
                        ->icon('heroicon-o-document')
                        ->color('gray')
                        ->action(function (Collection $records, $livewire) {
                            $ids = $records
                                ->pluck('id')
                                ->implode(',');

                            $url = route('asset.qr.bulk-label', [
                                'assets' => $ids,
                                'paper' => 'a4',
                            ]);

                            $livewire->js(
                                'window.open(' . json_encode($url) . ", '_blank')"
                            );
                        }),

                    /*
                    |--------------------------------------------------------------------------
                    | Hapus Aset
                    |--------------------------------------------------------------------------
                    */
                    DeleteBulkAction::make()
                        ->label('Hapus Aset')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),

                ])
                    ->label('Tindakan'),
            ])

            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateHeading('Belum ada aset')
            ->emptyStateDescription(
                'Tambahkan aset pertama atau gunakan menu Impor Aset untuk memasukkan data inventaris.'
            );
    }
}