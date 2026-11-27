<?php

namespace App\Models;

use Database\Factories\BorrowerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
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

        static::saved(function (Borrower $borrower): void {
            if ($borrower->type !== 'student'
                || ! $borrower->is_active
                || blank($borrower->nis)
                || $borrower->academic_year !== SchoolSetting::query()->value('academic_year')) {
                return;
            }

            StudentAccount::query()->firstOrCreate(
                ['nis' => $borrower->nis],
                [
                    'password' => Hash::make($borrower->nis),
                    'must_change_password' => true,
                ],
            );
        });
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
