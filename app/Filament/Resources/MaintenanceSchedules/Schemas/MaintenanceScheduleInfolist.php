<?php

namespace App\Filament\Resources\MaintenanceSchedules\Schemas;

use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jadwal & Pengingat')->schema([
                TextEntry::make('title')->label('Kegiatan'),
                TextEntry::make('reminder')->label('Status')->state(fn (MaintenanceSchedule $record): string => $record->reminderLabel())->badge(),
                TextEntry::make('asset_code')->label('Kode Perangkat'),
                TextEntry::make('asset_name')->label('Nama Perangkat'),
                TextEntry::make('due_date')->label('Perawatan Berikutnya')->date('d M Y'),
                TextEntry::make('interval_days')->label('Interval (hari)')->placeholder('Sekali saja'),
                TextEntry::make('technician')->label('Penanggung Jawab')->placeholder('Belum ditentukan'),
                TextEntry::make('last_completed_at')->label('Terakhir Selesai')->dateTime('d M Y H:i')->placeholder('Belum pernah'),
                TextEntry::make('notes')->label('Petunjuk')->columnSpanFull()->placeholder('-'),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('reports')->label('Riwayat Laporan dari Jadwal Ini')->schema([
                TextEntry::make('title')->label('Laporan')
                    ->url(fn (MaintenanceReport $record): string => MaintenanceReportResource::getUrl('view', ['record' => $record])),
                TextEntry::make('status')->label('Status')->formatStateUsing(fn (string $state): string => MaintenanceReport::STATUSES[$state] ?? $state),
                TextEntry::make('schedule_due_date')->label('Jadwal Saat Dibuat')->date('d M Y'),
                TextEntry::make('closed_at')->label('Ditutup')->dateTime('d M Y H:i')->placeholder('-'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
