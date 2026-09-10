<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->placeholder('Contoh: Router')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('asset_prefix')
                            ->label('Prefix Aset')
                            ->placeholder('Contoh: RT')
                            ->helperText('Digunakan untuk kode aset, misalnya GT-RT-0001.')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}