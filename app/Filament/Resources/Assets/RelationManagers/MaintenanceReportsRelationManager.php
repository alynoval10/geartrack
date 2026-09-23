<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Models\MaintenanceReport;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceReports';

    protected static ?string $title = 'Kerusakan & Perawatan';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('title')->label('Laporan')->searchable(),
            TextColumn::make('type')->label('Jenis')->formatStateUsing(fn (string $state): string => MaintenanceReport::TYPES[$state] ?? $state),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => MaintenanceReport::STATUSES[$state] ?? $state),
            TextColumn::make('technician')->label('Teknisi')->placeholder('-'),
            TextColumn::make('created_at')->label('Dilaporkan')->dateTime('d M Y H:i'),
            TextColumn::make('closed_at')->label('Ditutup')->dateTime('d M Y H:i')->placeholder('-'),
        ])->recordActions([
            Action::make('report')->label('Riwayat Penanganan')
                ->url(fn (MaintenanceReport $record): string => MaintenanceReportResource::getUrl('view', ['record' => $record])),
        ]);
    }
}
