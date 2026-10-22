<?php

namespace App\Filament\Resources\StockItems\Schemas;

use App\Models\Category;
use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Barang')
                ->description('Barang habis pakai dikelola sebagai jumlah stok, tanpa kode QR per unit.')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Barang')
                        ->placeholder('Contoh: BHP-KBL-001')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('Nama Barang')
                        ->placeholder('Contoh: Kabel UTP Cat6')
                        ->required()
                        ->maxLength(255),
                    Select::make('category_id')
                        ->label('Kategori')
                        ->options(fn (): array => Category::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('unit')
                        ->label('Satuan')
                        ->placeholder('Contoh: meter, buah, roll')
                        ->required()
                        ->maxLength(40),
                    Select::make('location_id')
                        ->label('Lokasi Penyimpanan')
                        ->options(fn (): array => Location::selectOptions())
                        ->searchable()
                        ->preload(),
                    TextInput::make('minimum_stock')
                        ->label('Batas Minimum')
                        ->numeric()
                        ->step('0.001')
                        ->maxValue(999999999.999)
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->rules(['decimal:0,3'])
                        ->helperText('GearTrack memberi tanda saat stok menyentuh atau berada di bawah batas ini.'),
                    TextInput::make('unit_price')
                        ->label('Harga Satuan (opsional)')
                        ->numeric()
                        ->prefix('Rp')
                        ->minValue(0)
                        ->maxValue(999999999999.99)
                        ->step('0.01')
                        ->rules(['decimal:0,2']),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->maxLength(3000)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
