<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\RelationManagers\LoanItemsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\MaintenanceReportsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\MaintenanceSchedulesRelationManager;
use App\Filament\Resources\Assets\RelationManagers\SpecificationsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\StockTakeItemsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\TransferItemsRelationManager;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Schemas\AssetInfolist;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Models\Asset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssetResource extends Resource
{
    protected static ?string $navigationLabel = 'Aset';

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Aset';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?int $navigationSort = 1;

    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Kolom aset dan relasi yang dapat ditemukan dari pencarian global.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'asset_code',
            'name',
            'serial_number',
            'model',
            'custodian_name',
            'custodian.name',
            'location.name',
            'assetSet.code',
            'assetSet.name',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['custodian', 'location', 'assetSet']);
    }

    /**
     * Tampilkan pembeda utama agar aset bernama serupa mudah dikenali.
     *
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Asset $record */
        return [
            'Kode' => $record->asset_code,
            'Lokasi' => $record->location?->name ?? '-',
            'Penanggung Jawab' => $record->custodian?->name ?? $record->custodian_name ?? '-',
            'Paket' => $record->assetSet?->name ?? '-',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SpecificationsRelationManager::class,
            LoanItemsRelationManager::class,
            TransferItemsRelationManager::class,
            StockTakeItemsRelationManager::class,
            MaintenanceReportsRelationManager::class,
            MaintenanceSchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
