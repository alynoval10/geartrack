<?php

namespace App\Filament\Resources\StockTakes;

use App\Filament\Resources\StockTakes\Pages\CreateStockTake;
use App\Filament\Resources\StockTakes\Pages\ListStockTakes;
use App\Filament\Resources\StockTakes\Pages\ViewStockTake;
use App\Filament\Resources\StockTakes\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\StockTakes\Schemas\StockTakeForm;
use App\Filament\Resources\StockTakes\Schemas\StockTakeInfolist;
use App\Filament\Resources\StockTakes\Tables\StockTakesTable;
use App\Models\StockTake;
use App\Services\OperationalAlertService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StockTakeResource extends Resource
{
    protected static ?string $model = StockTake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = $user ? app(OperationalAlertService::class)->openStockTakes($user)->count() : 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return StockTakeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockTakeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTakesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTakes::route('/'),
            'create' => CreateStockTake::route('/create'),
            'view' => ViewStockTake::route('/{record}'),

        ];
    }
}
