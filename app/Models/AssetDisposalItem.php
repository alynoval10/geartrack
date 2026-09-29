<?php

namespace App\Models;

use Database\Factories\AssetDisposalItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposalItem extends Model
{
    /** @use HasFactory<AssetDisposalItemFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'asset_code',
        'asset_name',
        'serial_number',
        'condition',
        'previous_status',
        'location_name',
        'custodian_name',
    ];

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(AssetDisposal::class, 'asset_disposal_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
