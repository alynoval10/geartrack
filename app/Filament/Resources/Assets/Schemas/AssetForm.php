<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('asset_code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'id')
                    ->required(),
                Select::make('brand_id')
                    ->relationship('brand', 'name'),
                TextInput::make('model'),
                TextInput::make('serial_number'),
                Select::make('location_id')
                    ->relationship('location', 'name'),
                TextInput::make('condition')
                    ->required()
                    ->default('good'),
                TextInput::make('status')
                    ->required()
                    ->default('available'),
                DatePicker::make('acquisition_date'),
                TextInput::make('funding_source'),
                TextInput::make('purchase_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('photo'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
