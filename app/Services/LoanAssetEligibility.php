<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class LoanAssetEligibility
{
    /**
     * Query perangkat yang aman ditawarkan pada transaksi peminjaman baru.
     *
     * @return Builder<Asset>
     */
    public function query(): Builder
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->whereIn('status', ['available', 'in_use'])
            ->where('condition', 'good')
            ->whereDoesntHave('loanItems', fn (Builder $query): Builder => $query->whereNotNull('active_asset_id'))
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

    public function findByQrToken(string $token): ?Asset
    {
        return $this->query()->where('qr_token', $token)->first();
    }

    /**
     * Ambil label perangkat terpilih dari tabel aset agar pilihan tetap terbaca ketika statusnya berubah.
     *
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

    public function label(Asset $asset): string
    {
        return collect([
            $asset->asset_code.' — '.$asset->name,
            $asset->category?->name,
            $asset->location?->name,
        ])->filter()->implode(' | ');
    }
}
