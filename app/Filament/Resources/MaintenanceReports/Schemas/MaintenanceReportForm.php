<?php

namespace App\Filament\Resources\MaintenanceReports\Schemas;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceReportForm
{
    public static function fields(): array
    {
        return [
            TextInput::make('title')->label('Judul Laporan')->placeholder('Contoh: PC tidak menyala')->required()->maxLength(150),
            Select::make('type')->label('Jenis Laporan')->options(MaintenanceReport::TYPES)->default('damage')->required(),
            Select::make('reported_condition')->label('Kondisi Saat Dilaporkan')
                ->options(MaintenanceReport::CONDITIONS)->default('minor_damage')->required(),
            Textarea::make('description')->label('Keluhan / Kebutuhan Perawatan')->required()->maxLength(5000)->rows(4)->columnSpanFull(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan Kerusakan dan Perawatan')
                ->description('Kondisi aset diperbarui saat laporan disimpan. Status perawatan dimulai lewat Mulai Penanganan.')
                ->schema([
                    Select::make('asset_id')->label('Aset')->relationship('asset', 'asset_code')
                        ->getOptionLabelFromRecordUsing(fn (Asset $record): string => $record->asset_code.' — '.$record->name)
                        ->searchable(['asset_code', 'name'])->preload()->required(),
                    ...self::fields(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
