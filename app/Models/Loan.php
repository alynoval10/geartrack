<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'borrower_name', 'borrower_contact', 'responsible_name', 'purpose',
        'asset_set_id', 'package_name', 'borrowed_at', 'due_date', 'status', 'returned_at', 'created_by'];

    protected function casts(): array
    {
        return ['borrowed_at' => 'datetime', 'due_date' => 'date', 'returned_at' => 'datetime'];
    }

    public function assetSet(): BelongsTo
    {
        return $this->belongsTo(AssetSet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_date->lt(today());
    }
}
