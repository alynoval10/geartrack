<?php

namespace App\Filament\Resources\MaintenanceReports\Schemas;

use App\Models\MaintenanceReport;
use App\Services\MaintenanceAssetEligibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MaintenanceReportForm
{
    public static function fields(): array
    {
        return [
            ToggleButtons::make('title_template')
                ->label('Saran Judul Laporan')
                ->options([
                    'Perangkat tidak menyala' => 'Perangkat tidak menyala',
                    'Koneksi jaringan bermasalah' => 'Koneksi jaringan bermasalah',
                    'Port atau kabel tidak berfungsi' => 'Port atau kabel tidak berfungsi',
                    'Perangkat berjalan lambat' => 'Perangkat berjalan lambat',
                    'Perawatan rutin perangkat' => 'Perawatan rutin perangkat',
                ])
                ->inline()
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (filled($state)) {
                        $set('title', $state);
                    }
                }),
            TextInput::make('title')
                ->label('Judul Laporan')
                ->placeholder('Contoh: PC tidak menyala')
                ->required()
                ->maxLength(150)
                ->helperText('Pilih saran di atas atau tulis judul secara manual.')
                ->columnSpanFull(),
            Select::make('type')->label('Jenis Laporan')->options(MaintenanceReport::TYPES)->default('damage')->required(),
            Select::make('reported_condition')->label('Kondisi Saat Dilaporkan')
                ->options(MaintenanceReport::CONDITIONS)->default('minor_damage')->required(),
            ToggleButtons::make('description_template')
                ->label('Saran Keluhan / Kebutuhan Perawatan')
                ->options([
                    'Perangkat tidak dapat dinyalakan saat digunakan.' => 'Tidak dapat dinyalakan',
                    'Koneksi jaringan terputus atau tidak stabil.' => 'Koneksi tidak stabil',
                    'Port atau kabel tidak berfungsi dan perlu diperiksa.' => 'Port/kabel bermasalah',
                    'Perangkat berjalan lambat dan perlu diperiksa.' => 'Kinerja perangkat lambat',
                    'Perangkat perlu dibersihkan dan diperiksa secara berkala.' => 'Pembersihan dan pemeriksaan rutin',
                ])
                ->inline()
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (filled($state)) {
                        $set('description', $state);
                    }
                }),
            Textarea::make('description')
                ->label('Keluhan / Kebutuhan Perawatan')
                ->required()
                ->maxLength(5000)
                ->rows(4)
                ->helperText('Pilih saran di atas atau tulis keluhan dan kebutuhan perawatan secara manual.')
                ->columnSpanFull(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan Kerusakan dan Perawatan')
                ->description('Kondisi aset diperbarui saat laporan disimpan. Status perawatan dimulai lewat Mulai Penanganan.')
                ->schema([
                    Select::make('asset_id')
                        ->label('Aset')
                        ->options(fn (MaintenanceAssetEligibility $assets): array => $assets->options())
                        ->getSearchResultsUsing(fn (string $search, MaintenanceAssetEligibility $assets): array => $assets->options($search))
                        ->getOptionLabelUsing(fn (mixed $value, MaintenanceAssetEligibility $assets): ?string => filled($value)
                            ? ($assets->labels([$value])[(int) $value] ?? null)
                            : null)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Daftar hanya menampilkan aset yang belum memiliki laporan aktif.'),
                    Actions::make([
                        Action::make('scanMaintenanceAsset')
                            ->label('Scan QR Aset')
                            ->icon('heroicon-o-qr-code')
                            ->color('info')
                            ->modalHeading('Scan QR Aset yang Dilaporkan')
                            ->modalDescription('Scan label QR aset. Jika berhasil, aset dipilih dan formulir laporan terbuka kembali.')
                            ->modalContent(fn () => view('filament.resources.maintenance-reports.maintenance-qr-scanner'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Selesai'),
                    ])
                        ->key('maintenanceAssetScannerActions'),
                    ...self::fields(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
