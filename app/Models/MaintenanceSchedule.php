<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id', 'asset_code', 'asset_name', 'title', 'due_date',
        'interval_days', 'technician', 'notes', 'is_active', 'last_completed_at', 'created_by'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'interval_days' => 'integer', 'is_active' => 'boolean', 'last_completed_at' => 'datetime'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('asset')
            ->whereDate('due_date', '<=', today()->addDays(7));
    }

    public function reminderLabel(): string
    {
        if (! $this->is_active || ! $this->asset_id) {
            return 'Nonaktif';
        }
        if ($this->due_date->lt(today())) {
            return 'Lewat Jadwal';
        }

        return $this->due_date->isToday() ? 'Hari Ini' : 'Terjadwal';
    }
}
