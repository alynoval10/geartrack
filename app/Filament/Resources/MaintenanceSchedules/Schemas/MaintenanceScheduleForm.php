<?php

namespace App\Filament\Resources\MaintenanceSchedules\Schemas;

use App\Models\Asset;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jadwal Perawatan')->schema([
                Select::make('asset_id')->label('Perangkat')->relationship('asset', 'asset_code')
                    ->getOptionLabelFromRecordUsing(fn (Asset $record): string => $record->asset_code.' — '.$record->name)
                    ->searchable(['asset_code', 'name'])->preload()->required()->disabledOn('edit')->dehydrated(),
                Actions::make([
                    Action::make('scanScheduleAsset')
                        ->label('Scan QR Perangkat')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->modalHeading('Scan QR Perangkat untuk Jadwal Perawatan')
                        ->modalDescription('Scan label QR perangkat. Jika berhasil, perangkat dipilih dan formulir jadwal terbuka kembali.')
                        ->modalContent(fn () => view('filament.resources.maintenance-schedules.schedule-qr-scanner'))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Selesai'),
                ])
                    ->key('scheduleAssetScannerActions')
                    ->visibleOn('create'),
                TextInput::make('title')->label('Kegiatan Perawatan')->required()->maxLength(150),
                DatePicker::make('due_date')->label('Tanggal Perawatan Berikutnya')->required(),
                TextInput::make('interval_days')->label('Ulangi Setiap (hari)')->numeric()->integer()->minValue(1)->maxValue(3650)->default(30)
                    ->helperText('Kosongkan untuk sekali saja. Jadwal berikutnya dihitung dari tanggal penyelesaian laporan.'),
                Select::make('technician_user_id')
                    ->label('Teknisi / Penanggung Jawab')
                    ->options(fn (): array => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->default(fn (): ?int => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Pilih pengguna aktif yang bertanggung jawab atas jadwal ini.'),
                Toggle::make('is_active')->label('Pengingat Aktif')->default(true)->required(),
                Textarea::make('notes')->label('Petunjuk Perawatan')->maxLength(5000)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
