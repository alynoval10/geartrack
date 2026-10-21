<?php

namespace App\Models;

use Database\Factories\BorrowerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
