<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Services\InventoryReportService;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

/** Menyusun halaman detail aset dengan istilah yang siap dipakai pengguna. */
class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto Aset')
                    ->description('Klik foto untuk melihat ukuran penuh.')
                    ->schema([
                        ImageEntry::make('photo')
                            ->hiddenLabel()
                            ->disk('public')
                            ->visibility('public')
                            ->height('18rem')
                            ->placeholder('Belum ada foto')
                            ->url(fn (Asset $record): ?string => filled($record->photo)
                                ? Storage::disk('public')->url($record->photo)
                                : null)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Identitas Aset')
                    ->schema([
                        TextEntry::make('asset_code')
                            ->label('Kode Aset')
                            ->badge(),
                        TextEntry::make('name')
                            ->label('Nama Aset'),
                        TextEntry::make('category.name')
                            ->label('Kategori')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('brand.name')
                            ->label('Merek')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('model')
                            ->label('Model / Tipe')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('serial_number')
                            ->label('Nomor Seri')
                            ->placeholder('Belum diisi'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Penempatan dan Kondisi')
                    ->schema([
                        TextEntry::make('location.name')
                            ->label('Lokasi')
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('custodian_name')
                            ->label('Penanggung Jawab')
                            ->state(fn (Asset $record): ?string => $record->custodian?->name ?? $record->custodian_name)
                            ->placeholder('Belum ditentukan'),
                        TextEntry::make('assetSet.name')
                            ->label('Paket Perangkat')
                            ->placeholder('Tidak tergabung dalam paket'),
                        TextEntry::make('set_role')
                            ->label('Peran dalam Paket')
                            ->formatStateUsing(fn (?string $state): string => Asset::SET_ROLES[$state] ?? $state ?? '-')
                            ->placeholder('Tidak ada'),
                        TextEntry::make('condition')
                            ->label('Kondisi')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => MaintenanceReport::CONDITIONS[$state] ?? $state ?? '-')
                            ->color(fn (?string $state): string => match ($state) {
                                'good' => 'success',
                                'minor_damage' => 'warning',
                                'major_damage' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => InventoryReportService::STATUSES[$state] ?? $state ?? '-')
                            ->color(fn (?string $state): string => match ($state) {
                                'available' => 'success',
                                'in_use' => 'info',
                                'borrowed', 'maintenance' => 'warning',
                                'lost' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Pengadaan dan Catatan')
                    ->schema([
                        TextEntry::make('acquisition_date')
                            ->label('Tanggal Pengadaan')
                            ->date('d M Y')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('funding_source')
                            ->label('Sumber Dana')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('purchase_price')
                            ->label('Harga Perolehan')
                            ->money('IDR', locale: 'id')
                            ->placeholder('Belum diisi'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Informasi Sistem')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
