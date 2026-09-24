<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceReport extends Model
{
    use HasFactory;

    public const TYPES = ['damage' => 'Kerusakan', 'maintenance' => 'Perawatan Rutin'];

    public const STATUSES = [
        'open' => 'Baru',
        'in_progress' => 'Ditangani',
        'resolved' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const CONDITIONS = [
        'good' => 'Baik',
        'minor_damage' => 'Rusak Ringan',
        'major_damage' => 'Rusak Berat',
    ];

    protected $fillable = [
        'asset_id', 'asset_code', 'asset_name', 'title', 'type', 'description',
        'reported_condition', 'status', 'technician', 'previous_asset_status',
        'reported_by', 'closed_at',
        'maintenance_schedule_id', 'schedule_due_date',
    ];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime', 'schedule_due_date' => 'date'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MaintenanceEntry::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }
}
