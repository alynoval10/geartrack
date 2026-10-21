<?php

namespace App\Filament\Resources\Borrowers;

use App\Filament\Resources\Borrowers\Pages\CreateBorrower;
use App\Filament\Resources\Borrowers\Pages\EditBorrower;
use App\Filament\Resources\Borrowers\Pages\ListBorrowers;
use App\Filament\Resources\Borrowers\Schemas\BorrowerForm;
use App\Filament\Resources\Borrowers\Tables\BorrowersTable;
use App\Models\Borrower;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BorrowerResource extends Resource
{
    protected static ?string $model = Borrower::class;

    protected static ?string $navigationLabel = 'Data Peminjam';

    protected static ?string $modelLabel = 'Data Peminjam';

    protected static ?string $pluralModelLabel = 'Data Peminjam';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return BorrowerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BorrowersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBorrowers::route('/'),
            'create' => CreateBorrower::route('/create'),
            'edit' => EditBorrower::route('/{record}/edit'),
        ];
    }
}
