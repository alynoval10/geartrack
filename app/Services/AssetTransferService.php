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
        // Tetap menerima pemanggilan lama dari service dengan menentukan jenis pilihan dari data yang tersedia.
        $data['selection_type'] ??= empty($data['asset_set_id']) ? 'asset' : 'package';

        $data = Validator::make($data, [
            'destination_location_id' => ['required', 'integer', 'exists:locations,id'],
            'sender_name' => ['required', 'string', 'max:150'],
            'receiver_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'receiver_name' => ['required_without:receiver_user_id', 'string', 'max:150'],
            'reason' => ['required', 'string', 'max:5000'],
            'selection_type' => ['required', 'in:asset,package'],
            'asset_set_id' => ['required_if:selection_type,package', 'nullable', 'integer', 'exists:asset_sets,id'],
            'asset_ids' => ['required_if:selection_type,asset', 'nullable', 'array', 'min:1', 'max:100'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['string', 'max:2048', 'regex:/\.(jpe?g|png|webp|pdf)$/i'],
        ])->validate();

        return DB::transaction(function () use ($data, $user): AssetTransfer {
            if ($data['selection_type'] === 'asset') {
                $data['asset_set_id'] = null;
            } else {
                $data['asset_ids'] = [];
            }

            $receiver = empty($data['receiver_user_id'])
                ? null
                : User::query()->whereKey($data['receiver_user_id'])->where('is_active', true)->lockForUpdate()->first();

            if (! empty($data['receiver_user_id']) && ! $receiver) {
                throw ValidationException::withMessages(['receiver_user_id' => 'Pilih user aktif sebagai penanggung jawab.']);
            }

            // Dokumen menyimpan nama sebagai snapshot, sementara aset menyimpan relasi user aktif.
            $data['receiver_name'] = $receiver?->name ?? $data['receiver_name'];
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
                ...collect($data)->except(['asset_ids', 'receiver_user_id', 'selection_type'])->all(),
                'code' => 'MUT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'package_name' => empty($data['asset_set_id']) ? null : AssetSet::findOrFail($data['asset_set_id'])->name,
                'destination_location_name' => $location->name,
                'transferred_at' => now(), 'created_by' => $user->id, 'created_by_name' => $user->name,
            ]);
            foreach ($assets as $asset) {
                $sourceLocationName = $asset->location?->name;
                $transfer->items()->create([
                    'asset_id' => $asset->id, 'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name, 'serial_number' => $asset->serial_number,
                    'condition' => $asset->condition, 'source_location_name' => $asset->location?->name,
                    'previous_custodian' => $asset->custodian_name,
                ]);
                $asset->update([
                    'location_id' => $location->id,
                    'custodian_user_id' => $receiver?->id,
                    'custodian_name' => $data['receiver_name'],
                ]);
                $asset->histories()->create([
                    'user_id' => $user->id, 'action' => 'transfer',
                    'field' => 'location_id',
                    'old_value' => $sourceLocationName,
                    'new_value' => $location->name,
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
