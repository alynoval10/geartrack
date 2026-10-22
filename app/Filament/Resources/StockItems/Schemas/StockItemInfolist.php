<?php

namespace App\Filament\Resources\StockItems\Schemas;

use App\Models\StockItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Stok')
                ->schema([
                    TextEntry::make('code')->label('Kode Barang'),
                    TextEntry::make('name')->label('Nama Barang'),
                    TextEntry::make('category.name')->label('Kategori')->placeholder('-'),
                    TextEntry::make('location.name')->label('Lokasi')->state(fn (StockItem $record): ?string => $record->location?->fullName())->placeholder('-'),
                    TextEntry::make('unit')->label('Satuan'),
                    TextEntry::make('current_stock')->label('Stok Tersedia')->numeric(decimalPlaces: 3)->suffix(fn (StockItem $record): string => ' '.$record->unit),
                    TextEntry::make('minimum_stock')->label('Batas Minimum')->numeric(decimalPlaces: 3)->suffix(fn (StockItem $record): string => ' '.$record->unit),
                    TextEntry::make('unit_price')->label('Harga Satuan')->money('IDR', locale: 'id')->placeholder('Belum diisi'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
