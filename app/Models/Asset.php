<?php

namespace App\Models;

use App\Services\AssetCodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $fillable = [
        'asset_code',
        'qr_token',
        'name',
        'category_id',
        'brand_id',
        'model',
        'serial_number',
        'location_id',
        'condition',
        'status',
        'acquisition_date',
        'funding_source',
        'purchase_price',
        'photo',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (empty($asset->qr_token)) {
                $asset->qr_token = (string) Str::uuid();
            }

            if (empty($asset->asset_code)) {
                $asset->asset_code = AssetCodeGenerator::generate(
                    $asset->category_id
                );
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function histories(): HasMany
{
    return $this->hasMany(AssetHistory::class)
        ->latest();
}
}