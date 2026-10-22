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
                ->description('Kode barang dibuat otomatis setelah disimpan. Barang habis pakai dikelola sebagai stok, tanpa kode QR per unit.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Barang')
                        ->placeholder('Contoh: Kabel UTP Cat6')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Contoh barang: kabel, konektor, atau bahan praktik.'),
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
                    TextInput::make('initial_stock')
                        ->label('Jumlah Tersedia')
                        ->numeric()
                        ->step('0.001')
                        ->maxValue(999999999.999)
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->rules(['decimal:0,3'])
                        ->visibleOn('create')
                        ->helperText('Jumlah awal akan otomatis dicatat sebagai transaksi barang masuk.'),
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
