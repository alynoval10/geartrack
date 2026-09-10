<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Aset')
                    ->description('Identitas utama perangkat atau alat.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Aset')
                            ->placeholder('Contoh: Router MikroTik')
                            ->required()
                            ->maxLength(255),

                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('brand_id')
                            ->label('Merek')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('model')
                            ->label('Model / Tipe')
                            ->placeholder('Contoh: RB952Ui-5ac2nD')
                            ->maxLength(255),

                        TextInput::make('serial_number')
                            ->label('Serial Number')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Penempatan & Kondisi')
                    ->schema([
                        Select::make('location_id')
                            ->label('Lokasi')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('condition')
                            ->label('Kondisi')
                            ->options([
                                'good' => 'Baik',
                                'minor_damage' => 'Rusak Ringan',
                                'major_damage' => 'Rusak Berat',
                            ])
                            ->default('good')
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Tersedia',
                                'in_use' => 'Digunakan',
                                'borrowed' => 'Dipinjam',
                                'maintenance' => 'Dalam Perawatan',
                                'lost' => 'Hilang',
                                'retired' => 'Dihapus / Nonaktif',
                            ])
                            ->default('available')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Informasi Pengadaan')
                    ->schema([
                        DatePicker::make('acquisition_date')
                            ->label('Tanggal Pengadaan'),

                        TextInput::make('funding_source')
                            ->label('Sumber Dana')
                            ->placeholder('Contoh: BOSP')
                            ->maxLength(255),

                        TextInput::make('purchase_price')
                            ->label('Harga Perolehan')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),
                    ])
                    ->columns(3),

                Section::make('Dokumentasi')
                    ->schema([
                        FileUpload::make('photo')
                            ->label('Foto Aset')
                            ->image()
                            ->imageEditor()
                            ->directory('assets')
                            ->disk('public'),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan mengenai aset...')
                            ->rows(5),
                    ])
                    ->columns(2),
            ]);
    }
}