<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetHistory;

class AssetObserver
{
    /**
     * Catat ketika aset pertama kali dibuat.
     */
    public function created(Asset $asset): void
    {
        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => 'Aset ditambahkan ke inventaris.',
        ]);
    }

    /**
     * Catat perubahan data aset.
     */
            public function updated(Asset $asset): void
        {
            $labels = [
                'name' => 'Nama Aset',
                'category_id' => 'Kategori',
                'brand_id' => 'Merek',
                'model' => 'Model / Tipe',
                'serial_number' => 'Nomor Seri',
                'location_id' => 'Lokasi',
                'condition' => 'Kondisi',
                'status' => 'Status',
                'acquisition_date' => 'Tanggal Perolehan',
                'funding_source' => 'Sumber Dana',
                'purchase_price' => 'Harga Perolehan',
                'notes' => 'Catatan',
            ];

            $changes = $asset->getChanges();
            $previous = $asset->getPrevious();

            foreach ($labels as $field => $label) {

                if (! array_key_exists($field, $changes)) {
                    continue;
                }

                $oldValue = $previous[$field] ?? null;
                $newValue = $changes[$field];

                AssetHistory::create([
                    'asset_id' => $asset->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'field' => $field,

                    'old_value' => $this->displayValue(
                        $asset,
                        $field,
                        $oldValue
                    ),

                    'new_value' => $this->displayValue(
                        $asset,
                        $field,
                        $newValue
                    ),

                    'description' => "{$label} diperbarui.",
                ]);
            }
        }

    private function displayValue(
        Asset $asset,
        string $field,
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'category_id' => \App\Models\Category::find($value)?->name
                ?? (string) $value,

            'brand_id' => \App\Models\Brand::find($value)?->name
                ?? (string) $value,

            'location_id' => \App\Models\Location::find($value)?->name
                ?? (string) $value,

            'condition' => match ($value) {
                'good' => 'Baik',
                'minor_damage' => 'Rusak Ringan',
                'major_damage' => 'Rusak Berat',
                default => (string) $value,
            },

            'status' => match ($value) {
                'available' => 'Tersedia',
                'borrowed' => 'Dipinjam',
                'maintenance' => 'Perawatan',
                'retired' => 'Tidak Digunakan',
                default => (string) $value,
            },

            'purchase_price' => 'Rp ' . number_format(
                (float) $value,
                0,
                ',',
                '.'
            ),

            default => (string) $value,
        };
    }
}