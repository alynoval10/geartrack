<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Models\MaintenanceSchedule;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceSchedules';

    protected static ?string $title = 'Jadwal Perawatan';

    public function table(Table $table): Table
    {
        return $table->defaultSort('due_date')->columns([
            TextColumn::make('title')->label('Kegiatan'),
            TextColumn::make('due_date')->label('Jadwal Berikutnya')->date('d M Y'),
            TextColumn::make('last_completed_at')->label('Terakhir Selesai')->dateTime('d M Y H:i')->placeholder('-'),
            TextColumn::make('reminder')->label('Status')->state(fn (MaintenanceSchedule $record): string => $record->reminderLabel())->badge(),
        ])->recordActions([
            Action::make('viewSchedule')->label('Lihat Jadwal')
                ->url(fn (MaintenanceSchedule $record): string => MaintenanceScheduleResource::getUrl('view', ['record' => $record])),
        ]);
    }
}
