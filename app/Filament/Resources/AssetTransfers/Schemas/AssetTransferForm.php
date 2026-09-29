<?php

namespace App\Filament\Resources\AssetTransfers\Schemas;

use App\Filament\Schemas\AssetSelectionFields;
use App\Models\Location;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
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
                    Select::make('receiver_user_id')
                        ->label('Penerima / Penanggung Jawab Baru')
                        ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => auth()->id())
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('sender_name')->label('Yang Menyerahkan')->default(fn (): string => auth()->user()->name)->required()->maxLength(150),
                    ToggleButtons::make('reason_template')
                        ->label('Saran Alasan / Keterangan')
                        ->options([
                            'Pemindahan aset ke ruang baru' => 'Pemindahan aset ke ruang baru',
                            'Pergantian penanggung jawab aset' => 'Pergantian penanggung jawab aset',
                            'Penataan ulang sarana laboratorium' => 'Penataan ulang sarana laboratorium',
                            'Penyerahan aset untuk perawatan' => 'Penyerahan aset untuk perawatan',
                            'Pengembalian aset ke ruang penyimpanan' => 'Pengembalian aset ke ruang penyimpanan',
                        ])
                        ->inline()
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (filled($state)) {
                                $set('reason', $state);
                            }
                        }),
                    Textarea::make('reason')
                        ->label('Alasan / Keterangan Serah Terima')
                        ->required()
                        ->maxLength(5000)
                        ->helperText('Pilih salah satu saran di atas atau tulis keterangan secara manual.')
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
