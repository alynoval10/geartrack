<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
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
        static::creating(function (Location $location) {
            if (empty($location->code)) {
                $nextNumber = ((int) static::max('id')) + 1;

                $location->code = 'LOC-' . str_pad(
                    $nextNumber,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}