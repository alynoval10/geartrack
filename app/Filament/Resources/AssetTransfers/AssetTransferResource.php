<?php

namespace App\Filament\Resources\AssetTransfers;

use App\Filament\Resources\AssetTransfers\Pages\CreateAssetTransfer;
use App\Filament\Resources\AssetTransfers\Pages\ListAssetTransfers;
use App\Filament\Resources\AssetTransfers\Pages\ViewAssetTransfer;
use App\Filament\Resources\AssetTransfers\Schemas\AssetTransferForm;
use App\Filament\Resources\AssetTransfers\Schemas\AssetTransferInfolist;
use App\Filament\Resources\AssetTransfers\Tables\AssetTransfersTable;
use App\Models\AssetTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetTransferResource extends Resource
{
    protected static ?string $navigationLabel = 'Mutasi Aset';

    protected static ?string $modelLabel = 'Mutasi Aset';

    protected static ?string $pluralModelLabel = 'Mutasi Aset';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    protected static ?string $model = AssetTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AssetTransferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetTransferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetTransfers::route('/'),
            'create' => CreateAssetTransfer::route('/create'),
            'view' => ViewAssetTransfer::route('/{record}'),
        ];
    }
}
