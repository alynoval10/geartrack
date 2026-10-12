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
