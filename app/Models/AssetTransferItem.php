<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransferItem extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id', 'asset_code', 'asset_name', 'serial_number', 'condition',
        'source_location_name', 'previous_custodian'];

    public function assetTransfer(): BelongsTo
    {
        return $this->belongsTo(AssetTransfer::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
