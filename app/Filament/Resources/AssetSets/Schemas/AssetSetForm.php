<?php

namespace App\Filament\Resources\AssetSets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssetSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Paket')
                    ->description(
                        'Kelompokkan beberapa aset yang digunakan sebagai satu paket perangkat.'
                    )
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Paket')
                            ->placeholder('Contoh: PC Lab 01')
                            ->required()
                            ->maxLength(150),

                        Select::make('location_id')
                            ->label('Lokasi')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih lokasi'),

                        ToggleButtons::make('accessories_template')
                            ->label('Saran Kelengkapan Tanpa QR')
                            ->options([
                                'Keyboard, Mouse, Mousepad' => 'Keyboard, Mouse, Mousepad',
                                'Keyboard dan Mouse' => 'Keyboard dan Mouse',
                                'Adaptor daya dan kabel daya' => 'Adaptor dan kabel daya',
                                'Kabel daya dan kabel display' => 'Kabel daya dan display',
                                'Remote dan adaptor daya' => 'Remote dan adaptor',
                            ])
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (filled($state)) {
                                    $set('accessories', $state);
                                }
                            }),

                        Textarea::make('accessories')
                            ->label('Kelengkapan Tanpa QR')
                            ->placeholder(
                                'Contoh: Keyboard, Mouse, Mousepad'
                            )
                            ->helperText(
                                'Pilih saran di atas atau tulis manual kelengkapan kecil yang tidak perlu memiliki QR sendiri.'
                            )
                            ->rows(3),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder(
                                'Catatan tambahan mengenai paket perangkat.'
                            )
                            ->rows(3),

                        Toggle::make('is_active')
                            ->label('Paket Aktif')
                            ->helperText(
                                'Nonaktifkan jika paket perangkat sudah tidak digunakan.'
                            )
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
