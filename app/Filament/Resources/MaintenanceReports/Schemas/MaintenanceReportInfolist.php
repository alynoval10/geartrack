<?php

namespace App\Filament\Resources\MaintenanceReports\Schemas;

use App\Models\MaintenanceReport;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan Perangkat')->schema([
                TextEntry::make('title')->label('Judul')->columnSpanFull(),
                TextEntry::make('asset_code')->label('Kode Aset'),
                TextEntry::make('asset_name')->label('Nama Aset'),
                TextEntry::make('type')->label('Jenis')->formatStateUsing(fn (string $state): string => MaintenanceReport::TYPES[$state] ?? $state),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => MaintenanceReport::STATUSES[$state] ?? $state),
                TextEntry::make('reported_condition')->label('Kondisi Awal')->formatStateUsing(fn (string $state): string => MaintenanceReport::CONDITIONS[$state] ?? $state),
                TextEntry::make('technician')->label('Teknisi / Penanggung Jawab')->placeholder('Belum ditentukan'),
                TextEntry::make('reporter.name')->label('Pelapor')->placeholder('Pengguna dihapus'),
                TextEntry::make('created_at')->label('Dilaporkan')->dateTime('d M Y H:i'),
                TextEntry::make('closed_at')->label('Ditutup')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('schedule_due_date')->label('Jadwal Asal')->date('d M Y')->placeholder('Tidak terkait jadwal'),
                TextEntry::make('total_cost')->label('Total Biaya')
                    ->state(fn (MaintenanceReport $record): string => (string) $record->entries()->sum('cost'))->money('IDR'),
                TextEntry::make('description')->label('Keluhan / Kebutuhan Perawatan')->columnSpanFull(),
            ])->columns(3)->columnSpanFull(),
        ]);
    }
}
