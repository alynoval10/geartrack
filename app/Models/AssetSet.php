<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetSet extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location_id',
        'accessories',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssetSet $assetSet) {
            if (empty($assetSet->code)) {
                $nextNumber = (static::max('id') ?? 0) + 1;

                $assetSet->code =
                    'SET-' . str_pad(
                        $nextNumber,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}