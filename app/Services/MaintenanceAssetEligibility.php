<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceAssetEligibility
{
    /**
     * Aset yang belum memiliki laporan kerusakan atau perawatan aktif.
     *
     * @return Builder<Asset>
     */
    public function query(): Builder
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->whereDoesntHave('maintenanceReports', fn (Builder $query): Builder => $query->whereIn('status', ['open', 'in_progress']));
    }

    /**
     * @return array<int, string>
     */
    public function options(?string $search = null): array
    {
        return $this->query()
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('asset_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('asset_code')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => $this->label($asset)])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $assetIds
     * @return array<int, string>
     */
    public function labels(array $assetIds): array
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->whereIn('id', $assetIds)
            ->get()
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => $this->label($asset)])
            ->all();
    }

    public function findByQrToken(string $token): ?Asset
    {
        return $this->query()->where('qr_token', $token)->first();
    }

    public function label(Asset $asset): string
    {
        return collect([
            $asset->asset_code.' — '.$asset->name,
            $asset->category?->name,
            $asset->location?->name,
        ])->filter()->implode(' | ');
    }
}
