<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Location extends Model
{
    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'description',
        'latitude',
        'longitude',
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
        static::saving(function (Location $location): void {
            if (! $location->parent_id || ($location->exists && ! $location->isDirty('parent_id'))) {
                return;
            }

            $parent = static::query()->find($location->parent_id);

            if (! $parent || $parent->parent_id || $parent->is($location)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Lokasi induk harus berupa lokasi utama.',
                ]);
            }

            if ($location->exists && $location->children()->exists()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Lokasi yang memiliki sub-lokasi tidak dapat dipindahkan ke lokasi induk lain.',
                ]);
            }
        });

        static::creating(function (Location $location) {
            if (empty($location->code)) {
                $nextNumber = ((int) static::max('id')) + 1;

                $location->code = 'LOC-'.str_pad(
                    $nextNumber,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function fullName(): string
    {
        return $this->parent
            ? $this->parent->name.' / '.$this->name
            : $this->name;
    }

    public function mapUrl(): ?string
    {
        $location = $this->hasMapCoordinates() ? $this : $this->parent;

        if (! $location?->hasMapCoordinates()) {
            return null;
        }

        $coordinates = $location->latitude.','.$location->longitude;

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($coordinates);
    }

    public function hasMapCoordinates(): bool
    {
        return filled($this->latitude) && filled($this->longitude);
    }

    /** @return array<int, string> */
    public static function selectOptions(bool $activeOnly = true): array
    {
        return static::query()
            ->with('parent:id,name')
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->mapWithKeys(fn (Location $location): array => [$location->id => $location->fullName()])
            ->all();
    }

    /** @return array<int, string> */
    public static function parentOptions(?self $record = null): array
    {
        if ($record?->children()->exists()) {
            return [];
        }

        return static::query()
            ->whereNull('parent_id')
            ->when($record, fn ($query) => $query->where('id', '!=', $record->getKey()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return list<int> */
    public function hierarchyIds(): array
    {
        return $this->children()->pluck('id')->prepend($this->id)->all();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
