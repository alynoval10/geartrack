<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetTransfer extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'asset_set_id', 'package_name', 'destination_location_id',
        'destination_location_name', 'sender_name', 'receiver_name', 'reason', 'transferred_at', 'created_by', 'created_by_name'];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetTransferItem::class);
    }

    public function assetSet(): BelongsTo
    {
        return $this->belongsTo(AssetSet::class);
    }
}
