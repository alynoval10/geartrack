<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AssetSelection
{
    /** @return Collection<int, Asset> */
    public function lock(array $data): Collection
    {
        $ids = array_map('intval', $data['asset_ids'] ?? []);
        if (! empty($data['asset_set_id'])) {
            $package = AssetSet::query()->lockForUpdate()->findOrFail($data['asset_set_id']);
            if (! $package->is_active) {
                throw ValidationException::withMessages(['asset_set_id' => 'Paket sudah nonaktif.']);
            }
            $members = $package->assets()->pluck('id')->all();
            if ($members === []) {
                throw ValidationException::withMessages(['asset_set_id' => 'Paket belum memiliki anggota.']);
            }
            $ids = array_merge($ids, $members);
        }
        $ids = array_values(array_unique($ids));
        if ($ids === [] || count($ids) > 100) {
            throw ValidationException::withMessages(['asset_ids' => 'Pilih 1–100 perangkat atau satu paket perangkat.']);
        }
        $assets = Asset::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        if ($assets->count() !== count($ids)) {
            throw ValidationException::withMessages(['asset_ids' => 'Sebagian aset tidak lagi tersedia. Pilih ulang.']);
        }

        return $assets;
    }
}
