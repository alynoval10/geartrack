<?php

namespace App\Filament\Resources\AssetTransfers\Schemas;

use App\Filament\Schemas\AssetSelectionFields;
use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mutasi & Serah Terima')->description('Lokasi dan penanggung jawab aset berubah saat disimpan. Dokumen yang sudah tercatat tidak dapat diedit; koreksi dicatat sebagai mutasi baru.')
                ->schema([
                    ...AssetSelectionFields::make(),
                    Select::make('destination_location_id')->label('Lokasi Tujuan')->required()->searchable()
                        ->options(fn (): array => Location::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
                    TextInput::make('receiver_name')->label('Penerima / Penanggung Jawab Baru')->required()->maxLength(150),
                    TextInput::make('sender_name')->label('Yang Menyerahkan')->default(fn (): string => auth()->user()->name)->required()->maxLength(150),
                    Textarea::make('reason')->label('Alasan / Keterangan Serah Terima')->required()->maxLength(5000)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
