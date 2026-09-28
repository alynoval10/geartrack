<?php

namespace App\Filament\Resources\AssetHistories;

use App\Filament\Resources\AssetHistories\Pages\ListAssetHistories;
use App\Filament\Resources\AssetHistories\Tables\AssetHistoriesTable;
use App\Models\AssetHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssetHistoryResource extends Resource
{
    protected static ?string $model = AssetHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'description';

    /**
     * Log aktivitas hanya dapat diperiksa oleh Administrator.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return AssetHistoriesTable::configure($table);
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
            'index' => ListAssetHistories::route('/'),
        ];
    }
}
