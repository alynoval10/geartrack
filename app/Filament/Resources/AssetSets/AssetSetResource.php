<?php

namespace App\Filament\Resources\AssetSets;

use App\Filament\Resources\AssetSets\Pages\CreateAssetSet;
use App\Filament\Resources\AssetSets\Pages\EditAssetSet;
use App\Filament\Resources\AssetSets\Pages\ListAssetSets;
use App\Filament\Resources\AssetSets\Pages\ViewAssetSet;
use App\Filament\Resources\AssetSets\RelationManagers\AssetsRelationManager;
use App\Filament\Resources\AssetSets\Schemas\AssetSetForm;
use App\Filament\Resources\AssetSets\Schemas\AssetSetInfolist;
use App\Filament\Resources\AssetSets\Tables\AssetSetsTable;
use App\Models\AssetSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssetSetResource extends Resource
{
    protected static ?string $model = AssetSet::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Paket Perangkat';

    protected static ?string $modelLabel = 'Paket Perangkat';

    protected static ?string $pluralModelLabel = 'Paket Perangkat';

    protected static string|UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AssetSetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetSetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AssetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetSets::route('/'),
            'create' => CreateAssetSet::route('/create'),
            'view' => ViewAssetSet::route('/{record}'),
            'edit' => EditAssetSet::route('/{record}/edit'),
        ];
    }
}