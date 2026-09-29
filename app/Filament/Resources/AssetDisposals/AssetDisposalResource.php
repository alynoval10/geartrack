<?php

namespace App\Filament\Resources\AssetDisposals;

use App\Filament\Resources\AssetDisposals\Pages\CreateAssetDisposal;
use App\Filament\Resources\AssetDisposals\Pages\ListAssetDisposals;
use App\Filament\Resources\AssetDisposals\Pages\ViewAssetDisposal;
use App\Filament\Resources\AssetDisposals\Schemas\AssetDisposalForm;
use App\Filament\Resources\AssetDisposals\Schemas\AssetDisposalInfolist;
use App\Filament\Resources\AssetDisposals\Tables\AssetDisposalsTable;
use App\Models\AssetDisposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AssetDisposalResource extends Resource
{
    protected static ?string $model = AssetDisposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;

    protected static ?string $navigationLabel = 'Penghapusan Aset';

    protected static ?string $modelLabel = 'Penghapusan Aset';

    protected static ?string $pluralModelLabel = 'Penghapusan Aset';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $recordTitleAttribute = 'code';

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
        return AssetDisposalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetDisposalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetDisposalsTable::configure($table);
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
            'index' => ListAssetDisposals::route('/'),
            'create' => CreateAssetDisposal::route('/create'),
            'view' => ViewAssetDisposal::route('/{record}'),
        ];
    }
}
