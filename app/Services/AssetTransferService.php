<?php

namespace App\Services;

use App\Models\AssetSet;
use App\Models\AssetTransfer;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetTransferService
{
    public function __construct(private AssetSelection $selection) {}

    public function transfer(array $data, User $user): AssetTransfer
    {
        $data = Validator::make($data, [
            'destination_location_id' => ['required', 'integer', 'exists:locations,id'],
            'sender_name' => ['required', 'string', 'max:150'],
            'receiver_name' => ['required', 'string', 'max:150'],
            'reason' => ['required', 'string', 'max:5000'],
            'asset_set_id' => ['nullable', 'integer', 'exists:asset_sets,id'],
            'asset_ids' => ['nullable', 'array', 'max:100'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
        ])->validate();

        return DB::transaction(function () use ($data, $user): AssetTransfer {
            $assets = $this->selection->lock($data);
            $location = Location::query()->lockForUpdate()->findOrFail($data['destination_location_id']);
            if (! $location->is_active) {
                throw ValidationException::withMessages(['destination_location_id' => 'Lokasi tujuan sudah nonaktif.']);
            }
            $assets->load('location');
            foreach ($assets as $asset) {
                if (in_array($asset->status, ['borrowed', 'lost'], true) || $asset->loanItems()->whereNotNull('active_asset_id')->exists()) {
                    throw ValidationException::withMessages(['asset_ids' => $asset->asset_code.' sedang dipinjam atau hilang. Selesaikan pencatatan terlebih dahulu.']);
                }
                if ($asset->location_id === $location->id && $asset->custodian_name === $data['receiver_name']) {
                    throw ValidationException::withMessages(['asset_ids' => $asset->asset_code.' sudah berada pada lokasi dan penanggung jawab tujuan.']);
                }
            }
            $transfer = AssetTransfer::create([
                ...collect($data)->except('asset_ids')->all(),
                'code' => 'MUT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'package_name' => empty($data['asset_set_id']) ? null : AssetSet::findOrFail($data['asset_set_id'])->name,
                'destination_location_name' => $location->name,
                'transferred_at' => now(), 'created_by' => $user->id, 'created_by_name' => $user->name,
            ]);
            foreach ($assets as $asset) {
                $transfer->items()->create([
                    'asset_id' => $asset->id, 'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name, 'serial_number' => $asset->serial_number,
                    'condition' => $asset->condition, 'source_location_name' => $asset->location?->name,
                    'previous_custodian' => $asset->custodian_name,
                ]);
                $asset->update(['location_id' => $location->id, 'custodian_name' => $data['receiver_name']]);
                $asset->histories()->create([
                    'user_id' => $user->id, 'action' => 'transfer',
                    'description' => "Mutasi {$transfer->code} ke {$location->name}, penanggung jawab {$transfer->receiver_name}.",
                ]);
            }
            if (! empty($data['asset_set_id'])) {
                AssetSet::findOrFail($data['asset_set_id'])->update(['location_id' => $location->id]);
            }

            return $transfer;
        });
    }
}
