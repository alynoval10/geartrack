<?php

namespace App\Filament\Schemas;

use App\Models\AssetSet;
use App\Services\TransferAssetEligibility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
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
