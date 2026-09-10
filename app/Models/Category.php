<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'code',
        'name',
        'asset_prefix',
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
        static::creating(function (Category $category) {
            if (empty($category->code)) {
                $nextNumber = ((int) static::max('id')) + 1;

                $category->code = 'CAT-' . str_pad(
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