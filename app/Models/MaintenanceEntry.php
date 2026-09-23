<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceEntry extends Model
{
    public const ACTIONS = [
        'reported' => 'Laporan Dibuat',
        'started' => 'Penanganan Dimulai',
        'note' => 'Catatan Tindakan',
        'resolved' => 'Penanganan Selesai',
        'cancelled' => 'Laporan Dibatalkan',
    ];

    protected $fillable = ['maintenance_report_id', 'user_id', 'action', 'notes', 'cost'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:2'];
    }

    public function maintenanceReport(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
