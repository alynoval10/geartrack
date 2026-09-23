<?php

namespace App\Filament\Resources\MaintenanceReports\Tables;

use App\Models\MaintenanceReport;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('title')->label('Laporan')->searchable()->weight('bold'),
            TextColumn::make('asset_code')->label('Kode Aset')->searchable(),
            TextColumn::make('asset_name')->label('Nama Aset')->searchable(),
            TextColumn::make('type')->label('Jenis')->formatStateUsing(fn (string $state): string => MaintenanceReport::TYPES[$state] ?? $state),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => MaintenanceReport::STATUSES[$state] ?? $state)
                ->color(fn (string $state): string => match ($state) {
                    'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', default => 'gray',
                }),
            TextColumn::make('technician')->label('Teknisi')->placeholder('-'),
            TextColumn::make('created_at')->label('Dilaporkan')->dateTime('d M Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(MaintenanceReport::STATUSES),
            SelectFilter::make('type')->label('Jenis')->options(MaintenanceReport::TYPES),
        ])->recordActions([ViewAction::make()->label('Tangani')]);
    }
}
