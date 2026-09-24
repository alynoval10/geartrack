<?php

namespace App\Models;

use App\Services\AssetCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Asset extends Model
{
    use HasFactory;

    public const SET_ROLES = [
        'main_pc' => 'PC Utama',
        'monitor' => 'Monitor',
        'device' => 'Perangkat Tambahan',
    ];

    protected $fillable = [
        'asset_code',
        'qr_token',
        'name',
        'category_id',
        'brand_id',
        'model',
        'serial_number',
        'location_id',
        'asset_set_id',
        'set_role',
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
        static::deleting(function (Asset $asset): void {
            if ($asset->loanItems()->whereNotNull('active_asset_id')->exists()) {
                throw ValidationException::withMessages(['asset' => 'Kembalikan perangkat sebelum menghapus aset.']);
            }
        });

        static::saving(function (Asset $asset) {
            if ($asset->exists && $asset->isDirty(['status', 'asset_set_id', 'set_role'])
                && $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                && ($asset->isDirty(['asset_set_id', 'set_role']) || ! in_array($asset->status, ['borrowed', 'lost'], true))) {
                throw ValidationException::withMessages(['status' => 'Aset masih dipinjam. Gunakan pengembalian sebelum mengubah status atau paket.']);
            }
            if (! $asset->asset_set_id) {
                $asset->set_role = null;
            } elseif ($asset->isDirty(['asset_set_id', 'set_role'])) {
                if (! array_key_exists($asset->set_role ?? '', self::SET_ROLES)) {
                    throw ValidationException::withMessages([
                        'set_role' => 'Pilih peran perangkat yang valid.',
                    ]);
                }
            }
        });

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

    public function assetSet(): BelongsTo
    {
        return $this->belongsTo(AssetSet::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class)
            ->latest();
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(AssetSpecification::class)
            ->orderBy('sort');
    }

    public function stockTakeItems(): HasMany
    {
        return $this->hasMany(StockTakeItem::class);
    }

    public function maintenanceReports(): HasMany
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}
