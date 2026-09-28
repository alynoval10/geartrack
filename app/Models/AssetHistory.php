<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHistory extends Model
{
    public const ACTIONS = [
        'created' => 'Ditambahkan',
        'updated' => 'Diubah',
        'transfer' => 'Dipindahkan',
        'deleted' => 'Dihapus',
        'stock_take' => 'Stock Opname',
        'loan' => 'Dipinjamkan',
        'loan_return' => 'Dikembalikan',
        'maintenance' => 'Perawatan',
    ];

    protected $fillable = [
        'asset_id',
        'asset_code',
        'asset_name',
        'user_id',
        'user_name',
        'action',
        'field',
        'old_value',
        'new_value',
        'description',
    ];

    /**
     * Isi snapshot identitas saat log dibuat agar tetap terbaca setelah data induk dihapus.
     */
    protected static function booted(): void
    {
        static::creating(function (AssetHistory $history): void {
            if ($history->asset_id && (blank($history->asset_code) || blank($history->asset_name))) {
                $asset = Asset::query()->find($history->asset_id);
                $history->asset_code ??= $asset?->asset_code;
                $history->asset_name ??= $asset?->name;
            }

            if ($history->user_id && blank($history->user_name)) {
                $history->user_name = User::query()->find($history->user_id)?->name;
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
