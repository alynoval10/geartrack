<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeItem extends Model
{
    public const RESULTS = [
        'pending' => 'Belum Diperiksa',
        'found' => 'Ditemukan',
        'missing' => 'Hilang',
        'moved' => 'Berpindah',
    ];

    protected $fillable = [
        'stock_take_id', 'asset_id', 'asset_code', 'asset_name',
        'expected_location_id', 'expected_location_name', 'original_status',
        'result', 'observed_location_id', 'observed_location_name',
        'notes', 'checked_by', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
