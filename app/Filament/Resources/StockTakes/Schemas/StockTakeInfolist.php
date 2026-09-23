<?php

namespace App\Filament\Resources\StockTakes\Schemas;

use App\Models\StockTake;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTakeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ringkasan Stock Opname')->schema([
                TextEntry::make('name')->label('Nama Sesi'),
                TextEntry::make('location_name')->label('Cakupan')->placeholder('Seluruh lokasi'),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => StockTake::STATUSES[$state] ?? $state),
                TextEntry::make('creator.name')->label('Dibuat Oleh')->placeholder('Pengguna dihapus'),
                TextEntry::make('created_at')->label('Dimulai')->dateTime('d M Y H:i'),
                TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('Masih berlangsung'),
                TextEntry::make('progress')->label('Hasil Pemeriksaan')
                    ->state(function (StockTake $record): string {
                        $counts = $record->items()->selectRaw('result, count(*) as total')->groupBy('result')->pluck('total', 'result');

                        return 'Belum diperiksa: '.($counts['pending'] ?? 0).' · Ditemukan: '.($counts['found'] ?? 0)
                            .' · Hilang: '.($counts['missing'] ?? 0).' · Berpindah: '.($counts['moved'] ?? 0);
                    })->columnSpanFull(),
                TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ])->columns(3)->columnSpanFull(),
        ]);
    }
}
