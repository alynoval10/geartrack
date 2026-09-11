<?php

namespace App\Filament\Exports;

use App\Models\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;
    
    

    public static function getColumns(): array
    {
        return [
        
            ExportColumn::make('number')
            ->label('No.')
            ->state(function (Asset $record): int {
                return Asset::where('id', '<=', $record->id)->count();
            }),
                
            ExportColumn::make('asset_code')
            ->label('Kode Aset'),

            ExportColumn::make('name')
                ->label('Nama Aset'),

            ExportColumn::make('category.name')
                ->label('Kategori'),

            ExportColumn::make('brand.name')
                ->label('Merek'),

            ExportColumn::make('model')
                ->label('Model / Tipe'),

            ExportColumn::make('serial_number')
                ->label('Nomor Seri'),

            ExportColumn::make('location.name')
                ->label('Lokasi'),

            ExportColumn::make('condition')
                ->label('Kondisi')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'good' => 'Baik',
                    'minor_damage' => 'Rusak Ringan',
                    'major_damage' => 'Rusak Berat',
                    default => $state ?? '-',
                }),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'available' => 'Tersedia',
                    'borrowed' => 'Dipinjam',
                    'maintenance' => 'Perawatan',
                    'retired' => 'Tidak Digunakan',
                    default => $state ?? '-',
                }),

            ExportColumn::make('acquisition_date')
                ->label('Tanggal Perolehan'),

            ExportColumn::make('funding_source')
                ->label('Sumber Dana'),

            ExportColumn::make('purchase_price')
                ->label('Harga Perolehan'),

            ExportColumn::make('notes')
                ->label('Catatan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export data aset selesai. '
            . number_format($export->successful_rows)
            . ' data berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '
                . number_format($failedRowsCount)
                . ' data gagal diekspor.';
        }

        return $body;
    }
}