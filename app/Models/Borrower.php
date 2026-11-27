<?php

namespace App\Models;

use Database\Factories\BorrowerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Borrower extends Model
{
    /** @use HasFactory<BorrowerFactory> */
    use HasFactory;

    public const TYPES = [
        'staff' => 'Guru / TU',
        'student' => 'Siswa',
    ];

    public const LEVELS = [
        'X' => 'X',
        'XI' => 'XI',
        'XII' => 'XII',
    ];

    protected $fillable = [
        'type',
        'name',
        'nis',
        'class_level',
        'class_name',
        'academic_year',
        'contact',
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
        static::creating(function (Borrower $borrower): void {
            if ($borrower->type === 'student' && blank($borrower->qr_token)) {
                $borrower->qr_token = (string) Str::uuid();
            }
        });
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
