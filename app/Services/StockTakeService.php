<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockTakeService
{
    public function start(array $data, User $user): StockTake
    {
        $data = Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($data, $user): StockTake {
            $location = ! empty($data['location_id']) ? Location::findOrFail($data['location_id']) : null;
            $assets = Asset::query()->with('location')
                ->when($location, fn ($query) => $query->where('location_id', $location->id));

            if (! $assets->exists()) {
                throw ValidationException::withMessages(['location_id' => 'Tidak ada aset untuk diperiksa dalam cakupan ini.']);
            }

            $stockTake = StockTake::create([
                ...$data,
                'location_name' => $location?->name,
                'status' => 'open',
                'created_by' => $user->id,
            ]);

            $assets->chunkById(200, function ($assets) use ($stockTake): void {
                foreach ($assets as $asset) {
                    $stockTake->items()->create([
                        'asset_id' => $asset->id,
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $asset->name,
                        'expected_location_id' => $asset->location_id,
                        'expected_location_name' => $asset->location?->name,
                        'original_status' => $asset->status,
                        'result' => 'pending',
                    ]);
                }
            });

            return $stockTake;
        });
    }

    public function record(StockTake $stockTake, StockTakeItem $item, array $data, User $user): StockTakeItem
    {
        $data = Validator::make($data, [
            'result' => ['required', Rule::in(['found', 'missing', 'moved'])],
            'observed_location_id' => ['required_if:result,moved', 'nullable', 'integer', 'exists:locations,id'],
            'notes' => ['required_if:result,missing', 'nullable', 'string', 'max:5000'],
        ], [
            'observed_location_id.required_if' => 'Pilih lokasi aktual untuk aset yang berpindah.',
            'notes.required_if' => 'Jelaskan hasil pencarian aset yang hilang.',
        ])->validate();

        return DB::transaction(function () use ($stockTake, $item, $data, $user): StockTakeItem {
            $session = StockTake::query()->lockForUpdate()->findOrFail($stockTake->id);
            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['result' => 'Sesi sudah selesai dan hasilnya tidak dapat diubah.']);
            }

            $item = $session->items()->lockForUpdate()->findOrFail($item->id);
            $asset = $item->asset_id ? Asset::query()->lockForUpdate()->find($item->asset_id) : null;
            if (! $asset && $data['result'] !== 'missing') {
                throw ValidationException::withMessages(['result' => 'Aset sudah dihapus. Catat sebagai hilang dengan penjelasan.']);
            }

            $location = $data['result'] === 'moved'
                ? Location::findOrFail($data['observed_location_id']) : null;

            if ($location && $location->id === $item->expected_location_id) {
                throw ValidationException::withMessages(['observed_location_id' => 'Lokasi masih sama dengan lokasi awal. Pilih Ditemukan.']);
            }

            if ($asset && $data['result'] === 'found' && $asset->location_id !== $item->expected_location_id) {
                throw ValidationException::withMessages(['result' => 'Lokasi aset sudah berubah dari daftar awal. Pilih Berpindah dan tentukan lokasi aktual.']);
            }

            if ($asset) {
                if ($data['result'] === 'missing') {
                    $asset->status = 'lost';
                } elseif ($asset->status === 'lost') {
                    $asset->status = $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                        ? 'borrowed'
                        : ($asset->maintenanceReports()->where('status', 'in_progress')->exists()
                        ? 'maintenance'
                        : (in_array($item->original_status, ['available', 'in_use', 'borrowed', 'retired'], true)
                            ? $item->original_status : 'available'));
                }

                if ($location) {
                    $asset->location_id = $location->id;
                }
                $asset->save();

                $asset->histories()->create([
                    'user_id' => $user->id,
                    'action' => 'stock_take',
                    'description' => "Stock opname {$session->name}: ".StockTakeItem::RESULTS[$data['result']].'.'
                        .($location ? ' Lokasi aktual: '.$location->name.'.' : '')
                        .(filled($data['notes'] ?? null) ? ' Catatan: '.$data['notes'] : ''),
                    'field' => 'stock_take_result',
                    'old_value' => StockTakeItem::RESULTS[$item->result],
                    'new_value' => StockTakeItem::RESULTS[$data['result']],
                ]);
            }

            $item->update([
                'result' => $data['result'],
                'observed_location_id' => $data['result'] === 'missing' ? null : ($location?->id ?? $item->expected_location_id),
                'observed_location_name' => $data['result'] === 'missing' ? null : ($location?->name ?? $item->expected_location_name),
                'notes' => $data['notes'] ?? null,
                'checked_by' => $user->id,
                'checked_at' => now(),
            ]);

            return $item;
        });
    }

    public function complete(StockTake $stockTake): void
    {
        DB::transaction(function () use ($stockTake): void {
            $session = StockTake::query()->lockForUpdate()->findOrFail($stockTake->id);
            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['status' => 'Sesi ini sudah selesai.']);
            }
            if ($session->items()->where('result', 'pending')->exists()) {
                throw ValidationException::withMessages(['status' => 'Masih ada aset yang belum diperiksa. Tentukan hasil semua aset sebelum menutup sesi.']);
            }
            $session->update(['status' => 'completed', 'completed_at' => now()]);
        });
    }
}
