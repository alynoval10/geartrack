<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetSet;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetDisposalService
{
    public function submit(array $data, User $user): AssetDisposal
    {
        $data = Validator::make($data, [
            'asset_ids' => ['required', 'array', 'min:1', 'max:100'],
            'asset_ids.*' => ['required', 'integer', 'distinct', 'exists:assets,id'],
            'reason_type' => ['required', Rule::in(array_keys(AssetDisposal::REASON_TYPES))],
            'reason' => ['required', 'string', 'max:5000'],
            'disposal_date' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        return DB::transaction(function () use ($data, $user): AssetDisposal {
            $assets = Asset::query()
                ->with(['location', 'assetSet'])
                ->whereIn('id', $data['asset_ids'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->ensureAssetsCanBeSubmitted($assets, $data['asset_ids']);

            $disposal = AssetDisposal::create([
                'code' => 'HAP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'reason_type' => $data['reason_type'],
                'reason' => $data['reason'],
                'disposal_date' => $data['disposal_date'],
                'status' => 'pending',
                'submitted_by' => $user->id,
                'submitted_by_name' => $user->name,
            ]);

            foreach ($assets as $asset) {
                $disposal->items()->create([
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name,
                    'serial_number' => $asset->serial_number,
                    'condition' => $asset->condition,
                    'previous_status' => $asset->status,
                    'location_name' => $asset->location?->name,
                    'custodian_name' => $asset->custodian_name,
                ]);
            }

            return $disposal;
        });
    }

    public function approve(AssetDisposal $disposal, User $reviewer, ?string $notes = null): AssetDisposal
    {
        $this->ensureAdministrator($reviewer);

        return DB::transaction(function () use ($disposal, $reviewer, $notes): AssetDisposal {
            $disposal = AssetDisposal::query()->with('items')->lockForUpdate()->findOrFail($disposal->id);
            $this->ensurePending($disposal);

            $assetIds = $disposal->items->pluck('asset_id')->filter()->map(fn ($id): int => (int) $id)->all();
            $assets = Asset::query()->whereIn('id', $assetIds)->orderBy('id')->lockForUpdate()->get();

            if (count($assetIds) !== $disposal->items->count() || $assets->count() !== count($assetIds)) {
                throw ValidationException::withMessages([
                    'assets' => 'Sebagian aset sudah tidak tersedia. Pengajuan tidak dapat disetujui.',
                ]);
            }

            $packageIds = $assets->pluck('asset_set_id')->filter()->unique()->values();

            foreach ($assets as $asset) {
                if (in_array($asset->status, ['retired', 'borrowed'], true)
                    || $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                    || $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages([
                        'assets' => $asset->asset_code.' belum dapat dipensiunkan karena masih dipinjam, sudah pensiun, atau memiliki perawatan aktif.',
                    ]);
                }

                $previousStatus = $asset->status;
                $asset->update([
                    'status' => 'retired',
                    'asset_set_id' => null,
                    'set_role' => null,
                ]);
                $asset->maintenanceSchedules()->update(['is_active' => false]);
                $asset->histories()->create([
                    'user_id' => $reviewer->id,
                    'action' => 'retirement',
                    'field' => 'status',
                    'old_value' => $previousStatus,
                    'new_value' => 'retired',
                    'description' => "Aset dipensiunkan melalui {$disposal->code}.",
                ]);
            }

            foreach ($packageIds as $packageId) {
                $package = AssetSet::query()->lockForUpdate()->find($packageId);
                if ($package && ! $package->assets()->exists()) {
                    $package->update(['is_active' => false]);
                }
            }

            $disposal->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_by_name' => $reviewer->name,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            return $disposal->refresh();
        });
    }

    public function reject(AssetDisposal $disposal, User $reviewer, string $notes): AssetDisposal
    {
        $this->ensureAdministrator($reviewer);
        $validated = Validator::make(['notes' => $notes], [
            'notes' => ['required', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($disposal, $reviewer, $validated): AssetDisposal {
            $disposal = AssetDisposal::query()->lockForUpdate()->findOrFail($disposal->id);
            $this->ensurePending($disposal);
            $disposal->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_by_name' => $reviewer->name,
                'reviewed_at' => now(),
                'review_notes' => $validated['notes'],
            ]);

            return $disposal->refresh();
        });
    }

    /** @param Collection<int, Asset> $assets */
    private function ensureAssetsCanBeSubmitted(Collection $assets, array $requestedIds): void
    {
        if ($assets->count() !== count(array_unique(array_map('intval', $requestedIds)))) {
            throw ValidationException::withMessages(['asset_ids' => 'Sebagian aset tidak lagi tersedia. Pilih ulang.']);
        }

        foreach ($assets as $asset) {
            if (in_array($asset->status, ['retired', 'borrowed'], true)
                || $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                || $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                throw ValidationException::withMessages([
                    'asset_ids' => $asset->asset_code.' belum dapat diajukan karena masih dipinjam, sudah pensiun, atau memiliki perawatan aktif.',
                ]);
            }

            $hasPendingRequest = $asset->disposalItems()
                ->whereHas('disposal', fn ($query) => $query->where('status', 'pending'))
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages([
                    'asset_ids' => $asset->asset_code.' sudah memiliki pengajuan penghapusan yang menunggu persetujuan.',
                ]);
            }
        }
    }

    private function ensureAdministrator(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang dapat memeriksa penghapusan aset.');
        }
    }

    private function ensurePending(AssetDisposal $disposal): void
    {
        if ($disposal->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diperiksa dan tidak dapat diubah lagi.',
            ]);
        }
    }
}
