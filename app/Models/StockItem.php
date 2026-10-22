<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class StockItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category_id',
        'location_id',
        'unit',
        'current_stock',
        'minimum_stock',
        'unit_price',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (StockItem $stockItem): void {
            if ($stockItem->movements()->exists()) {
                throw ValidationException::withMessages([
                    'stock_item' => 'Barang yang sudah memiliki riwayat transaksi tidak dapat dihapus. Nonaktifkan barang tersebut.',
                ]);
            }
        });
    }

    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('current_stock', '<=', 'minimum_stock');
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
