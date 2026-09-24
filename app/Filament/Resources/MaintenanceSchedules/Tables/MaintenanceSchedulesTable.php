<?php

namespace App\Filament\Resources\MaintenanceSchedules\Tables;

use App\Models\MaintenanceSchedule;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MaintenanceSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('due_date')->columns([
            TextColumn::make('asset_code')->label('Kode')->searchable(),
            TextColumn::make('asset_name')->label('Perangkat')->searchable(),
            TextColumn::make('title')->label('Kegiatan')->searchable(),
            TextColumn::make('due_date')->label('Jadwal')->date('d M Y')->sortable(),
            TextColumn::make('reminder')->label('Pengingat')->state(fn (MaintenanceSchedule $record): string => $record->reminderLabel())
                ->badge()->color(fn (string $state): string => match ($state) {
                    'Lewat Jadwal' => 'danger', 'Hari Ini' => 'warning', 'Nonaktif' => 'gray', default => 'info',
                }),
            TextColumn::make('technician')->label('Penanggung Jawab')->placeholder('-'),
        ])->filters([
            Filter::make('overdue')->label('Lewat Jadwal')->query(fn ($query) => $query->where('is_active', true)->whereHas('asset')->whereDate('due_date', '<', today())),
            Filter::make('upcoming')->label('Sampai 7 Hari ke Depan')->query(fn ($query) => $query->upcoming()),
            TernaryFilter::make('is_active')->label('Aktif'),
        ])->recordActions([ViewAction::make(), EditAction::make()])->toolbarActions([]);
    }
}
