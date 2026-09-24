<?php

namespace App\Filament\Schemas;

use App\Models\Asset;
use App\Models\AssetSet;
use Filament\Forms\Components\Select;

class AssetSelectionFields
{
    public static function make(): array
    {
        return [
            Select::make('asset_set_id')->label('Paket Perangkat (opsional)')
                ->options(fn (): array => AssetSet::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->helperText('Semua anggota paket pada saat transaksi akan disertakan.'),
            Select::make('asset_ids')->label('Perangkat / Tambahan Anggota')->multiple()->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Asset::query()
                    ->where(fn ($query) => $query->where('asset_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->limit(50)->get()->mapWithKeys(fn (Asset $asset): array => [$asset->id => $asset->asset_code.' — '.$asset->name])->all())
                ->getOptionLabelsUsing(fn (array $values): array => Asset::whereIn('id', $values)->get()
                    ->mapWithKeys(fn (Asset $asset): array => [$asset->id => $asset->asset_code.' — '.$asset->name])->all())
                ->helperText('Pilih minimal satu perangkat atau satu paket. Maksimal 100 perangkat.'),
        ];
    }
}
