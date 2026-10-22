<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Lokasi')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->placeholder('Contoh: Lab WK1 atau Lemari 001')
                            ->required()
                            ->maxLength(255),

                        Select::make('parent_id')
                            ->label('Lokasi Induk')
                            ->options(fn (?Location $record): array => Location::parentOptions($record))
                            ->searchable()
                            ->placeholder('Lokasi utama (tanpa induk)')
                            ->helperText('Kosongkan untuk lokasi utama. Pilih lokasi utama untuk membuat sub-lokasi, seperti Lemari 001 di Lab WK1.')
                            ->disabled(fn (?Location $record): bool => $record?->children()->exists() ?? false),

                        Section::make('Titik Google Maps')
                            ->description('Koordinat bersifat opsional. Jika sub-lokasi tidak memiliki titik sendiri, GearTrack menggunakan titik lokasi induk.')
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step('0.0000001')
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->rules(['nullable', 'numeric', 'between:-90,90', 'decimal:0,7', 'required_with:longitude'])
                                    ->placeholder('Contoh: -6.4450000')
                                    ->helperText('Di Google Maps, klik kanan titik lokasi lalu salin koordinat pertama.'),

                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step('0.0000001')
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->rules(['nullable', 'numeric', 'between:-180,180', 'decimal:0,7', 'required_with:latitude'])
                                    ->placeholder('Contoh: 108.1230000')
                                    ->helperText('Salin koordinat kedua dari Google Maps.'),
                            ])
                            ->columns(2),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Contoh: Laboratorium utama TJKT')
                            ->rows(3),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }
}
