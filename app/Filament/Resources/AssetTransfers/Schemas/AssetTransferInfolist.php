<?php

namespace App\Filament\Resources\AssetTransfers\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetTransferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Berita Acara Mutasi')->schema([
                TextEntry::make('code')->label('Nomor'),
                TextEntry::make('transferred_at')->label('Tanggal')->dateTime('d M Y H:i'),
                TextEntry::make('destination_location_name')->label('Lokasi Tujuan'),
                TextEntry::make('package_name')->label('Paket Saat Mutasi')->placeholder('-'),
                TextEntry::make('sender_name')->label('Yang Menyerahkan'),
                TextEntry::make('receiver_name')->label('Penerima'),
                TextEntry::make('reason')->label('Keterangan')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            RepeatableEntry::make('items')->label('Perangkat yang Diserahterimakan')->schema([
                TextEntry::make('asset_code')->label('Kode'),
                TextEntry::make('asset_name')->label('Nama'),
                TextEntry::make('source_location_name')->label('Lokasi Asal')->placeholder('-'),
                TextEntry::make('previous_custodian')->label('Penanggung Jawab Sebelumnya')->placeholder('-'),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
