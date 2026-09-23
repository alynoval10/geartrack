<?php

namespace App\Filament\Resources\AssetSets\Schemas;

use App\Models\AssetSet;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetSetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Paket')->schema([
                    TextEntry::make('code')->label('Kode Paket')->badge(),
                    TextEntry::make('name')->label('Nama Paket'),
                    TextEntry::make('location.name')->label('Lokasi Paket')->placeholder('Belum ditentukan'),
                    IconEntry::make('is_active')->label('Paket Aktif')->boolean(),
                    TextEntry::make('member_count')->label('Jumlah Anggota')
                        ->state(fn (AssetSet $record): int => $record->assets()->count())->suffix(' perangkat'),
                    TextEntry::make('accessories')->label('Kelengkapan Tanpa QR')->placeholder('Belum diisi'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('Tidak ada catatan')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }
}
