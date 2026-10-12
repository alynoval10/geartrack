<?php

namespace App\Filament\Resources\StockTakes\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTakeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mulai Stock Opname')
                ->description('Daftar aset dan lokasi awal disimpan saat sesi dibuat. Aset baru setelah itu diperiksa pada sesi berikutnya.')
                ->schema([
                    TextInput::make('name')->label('Nama Sesi')->placeholder('Contoh: Pemeriksaan Lab September 2026')->required()->maxLength(150),
                    Select::make('location_id')->label('Cakupan Lokasi')->options(fn (): array => Location::selectOptions(activeOnly: false))
                        ->searchable()->preload()->placeholder('Seluruh lokasi'),
                    Textarea::make('notes')->label('Catatan')->maxLength(5000)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
