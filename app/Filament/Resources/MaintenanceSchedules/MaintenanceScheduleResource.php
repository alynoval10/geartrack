<?php

namespace App\Filament\Resources\MaintenanceSchedules;

use App\Filament\Resources\MaintenanceSchedules\Pages\CreateMaintenanceSchedule;
use App\Filament\Resources\MaintenanceSchedules\Pages\EditMaintenanceSchedule;
use App\Filament\Resources\MaintenanceSchedules\Pages\ListMaintenanceSchedules;
use App\Filament\Resources\MaintenanceSchedules\Pages\ViewMaintenanceSchedule;
use App\Filament\Resources\MaintenanceSchedules\Schemas\MaintenanceScheduleForm;
use App\Filament\Resources\MaintenanceSchedules\Schemas\MaintenanceScheduleInfolist;
use App\Filament\Resources\MaintenanceSchedules\Tables\MaintenanceSchedulesTable;
use App\Models\MaintenanceSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaintenanceScheduleResource extends Resource
{
    protected static ?string $navigationLabel = 'Jadwal Perawatan';

    protected static ?string $modelLabel = 'Jadwal Perawatan';

    protected static ?string $pluralModelLabel = 'Jadwal Perawatan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function canEdit(Model $record): bool
    {
        return $record->asset_id !== null && ! $record->reports()->whereIn('status', ['open', 'in_progress'])->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MaintenanceSchedule::query()->upcoming()->whereDate('due_date', '<=', today())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    protected static ?string $model = MaintenanceSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MaintenanceScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaintenanceScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceSchedulesTable::configure($table);
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
            'index' => ListMaintenanceSchedules::route('/'),
            'create' => CreateMaintenanceSchedule::route('/create'),
            'view' => ViewMaintenanceSchedule::route('/{record}'),
            'edit' => EditMaintenanceSchedule::route('/{record}/edit'),
        ];
    }
}
