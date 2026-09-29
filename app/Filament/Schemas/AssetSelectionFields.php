<?php

namespace App\Filament\Schemas;

use App\Models\AssetSet;
use App\Services\TransferAssetEligibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class AssetSelectionFields
{
    public static function make(): array
    {
        return [
            ToggleButtons::make('selection_type')
                ->label('Jenis Aset yang Dimutasi')
                ->options([
                    'asset' => 'Perangkat Satuan',
                    'package' => 'Paket Perangkat',
                ])
                ->default('asset')
                ->grouped()
                ->inline()
                ->live()
                ->required()
                ->columnSpanFull()
                ->afterStateUpdated(function (Set $set): void {
                    $set('asset_ids', []);
                    $set('asset_set_id', null);
                }),
            Select::make('asset_ids')
                ->label('Pilih Perangkat Satuan')
                ->multiple()
                ->options(fn (TransferAssetEligibility $assets): array => $assets->options())
                ->getSearchResultsUsing(fn (string $search, TransferAssetEligibility $assets): array => $assets->options($search))
                ->getOptionLabelsUsing(fn (array $values, TransferAssetEligibility $assets): array => $assets->labels($values))
                ->searchable()
                ->preload()
                ->minItems(1)
                ->maxItems(100)
                ->required(fn (Get $get): bool => $get('selection_type') === 'asset')
                ->visible(fn (Get $get): bool => $get('selection_type') === 'asset')
                ->helperText('Pilih satu atau beberapa perangkat yang tidak menjadi anggota paket.'),
            Actions::make([
                Action::make('scanTransferAssets')
                    ->label('Scan QR Perangkat')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading('Scan QR Perangkat yang Dimutasi')
                    ->modalDescription('Scan satu label QR. Jika berhasil, perangkat ditambahkan dan formulir mutasi terbuka kembali.')
                    ->modalContent(fn () => view('filament.resources.asset-transfers.transfer-qr-scanner'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Selesai'),
            ])
                ->key('transferAssetScannerActions')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('selection_type') === 'asset'),
            Select::make('asset_set_id')
                ->label('Pilih Paket Perangkat')
                ->options(fn (): array => AssetSet::query()
                    ->where('is_active', true)
                    ->withCount('assets')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (AssetSet $assetSet): array => [
                        $assetSet->id => "{$assetSet->code} — {$assetSet->name} ({$assetSet->assets_count} perangkat)",
                    ])->all())
                ->searchable()
                ->preload()
                ->required(fn (Get $get): bool => $get('selection_type') === 'package')
                ->visible(fn (Get $get): bool => $get('selection_type') === 'package')
                ->helperText('Semua anggota paket pada saat transaksi akan dimutasi bersama.'),
        ];
    }
}
