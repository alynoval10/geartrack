<?php

namespace App\Filament\Resources\AssetDisposals\Schemas;

use App\Filament\Schemas\EvidenceAttachments;
use App\Models\AssetDisposal;
use App\Models\MaintenanceReport;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetDisposalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pengajuan Penghapusan Aset')->schema([
                TextEntry::make('code')->label('Nomor Pengajuan'),
                TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => AssetDisposal::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                TextEntry::make('reason_type')->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => AssetDisposal::REASON_TYPES[$state] ?? $state),
                TextEntry::make('disposal_date')->label('Tanggal Diusulkan')->date('d M Y'),
                TextEntry::make('submitted_by_name')->label('Diajukan Oleh'),
                TextEntry::make('reviewed_by_name')->label('Diperiksa Oleh')->placeholder('Belum diperiksa'),
                TextEntry::make('reviewed_at')->label('Waktu Pemeriksaan')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('reason')->label('Alasan / Dasar')->columnSpanFull(),
                TextEntry::make('review_notes')->label('Catatan Pemeriksaan')->placeholder('-')->columnSpanFull(),
                EvidenceAttachments::entry(),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('items')->label('Daftar Aset')->schema([
                TextEntry::make('asset_code')->label('Kode'),
                TextEntry::make('asset_name')->label('Nama'),
                TextEntry::make('serial_number')->label('Nomor Seri')->placeholder('-'),
                TextEntry::make('condition')->label('Kondisi')
                    ->formatStateUsing(fn (string $state): string => MaintenanceReport::CONDITIONS[$state] ?? $state),
                TextEntry::make('location_name')->label('Lokasi')->placeholder('-'),
                TextEntry::make('custodian_name')->label('Penanggung Jawab')->placeholder('-'),
            ])->columns(3)->columnSpanFull(),
        ]);
    }
}
