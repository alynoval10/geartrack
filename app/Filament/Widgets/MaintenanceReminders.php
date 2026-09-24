<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Models\MaintenanceSchedule;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MaintenanceReminders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table->heading('Pengingat Perawatan')
            ->description('Jadwal terlewat, hari ini, dan tujuh hari ke depan.')
            ->query(MaintenanceSchedule::query()->upcoming())->defaultSort('due_date')
            ->columns([
                TextColumn::make('asset_code')->label('Kode Aset'),
                TextColumn::make('asset_name')->label('Perangkat'),
                TextColumn::make('title')->label('Perawatan'),
                TextColumn::make('due_date')->label('Jadwal')->date('d M Y'),
                TextColumn::make('reminder')->label('Status')->state(fn (MaintenanceSchedule $record): string => $record->reminderLabel())
                    ->badge()->color(fn (string $state): string => $state === 'Lewat Jadwal' ? 'danger' : 'warning'),
            ])->recordActions([
                Action::make('viewSchedule')->label('Lihat Jadwal')
                    ->url(fn (MaintenanceSchedule $record): string => MaintenanceScheduleResource::getUrl('view', ['record' => $record])),
            ])->paginated([5, 10])->defaultPaginationPageOption(5)
            ->emptyStateHeading('Tidak ada perawatan yang perlu segera dilakukan');
    }
}
