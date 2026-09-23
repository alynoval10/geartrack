<?php

namespace App\Filament\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;

class StockTakeResultFields
{
    public static function make(): array
    {
        return [
            Select::make('result')->label('Hasil Pemeriksaan')
                ->options(['found' => 'Ditemukan', 'missing' => 'Hilang', 'moved' => 'Berpindah'])
                ->required()->live()
                ->helperText('Hilang memperbarui status aset. Berpindah memperbarui lokasi aset, bukan lokasi seluruh paket.'),
            Select::make('observed_location_id')->label('Lokasi Aktual')
                ->options(fn (): array => Location::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->visible(fn (Get $get): bool => $get('result') === 'moved')
                ->required(fn (Get $get): bool => $get('result') === 'moved'),
            Textarea::make('notes')->label('Catatan Pemeriksaan')->maxLength(5000)->rows(3)
                ->required(fn (Get $get): bool => $get('result') === 'missing')
                ->helperText('Untuk aset hilang, jelaskan lokasi dan upaya pencarian.'),
        ];
    }
}
