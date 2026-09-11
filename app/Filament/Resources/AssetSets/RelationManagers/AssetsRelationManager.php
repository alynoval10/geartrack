<?php

namespace App\Filament\Resources\AssetSets\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    protected static ?string $title = 'Anggota Paket';

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Aset';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Aset'),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->placeholder('-'),

                SelectColumn::make('set_role')
                    ->label('Peran Dalam Paket')
                    ->options([
                        'main_pc' => 'PC Utama',
                        'monitor' => 'Monitor',
                        'device' => 'Perangkat Tambahan',
                    ])
                    ->placeholder('Pilih peran'),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->label('Tambahkan Aset')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns([
                        'asset_code',
                        'name',
                    ]),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Keluarkan dari Paket'),
            ]);
    }
}