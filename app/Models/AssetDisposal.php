<?php

namespace App\Models;

use Database\Factories\AssetDisposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDisposal extends Model
{
    /** @use HasFactory<AssetDisposalFactory> */
    use HasFactory;

    public const REASON_TYPES = [
        'major_damage' => 'Rusak Berat / Tidak Ekonomis Diperbaiki',
        'permanently_lost' => 'Hilang Permanen',
        'sold' => 'Dijual / Dilelang',
        'destroyed' => 'Dimusnahkan',
        'other' => 'Alasan Lain',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu Persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    protected $fillable = [
        'code',
        'reason_type',
        'reason',
        'disposal_date',
        'status',
        'submitted_by',
        'submitted_by_name',
        'reviewed_by',
        'reviewed_by_name',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetDisposalItem::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
