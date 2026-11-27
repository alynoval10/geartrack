<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StudentAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['nis', 'password', 'must_change_password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function currentBorrower(): ?Borrower
    {
        $academicYear = SchoolSetting::query()->value('academic_year');

        if (blank($academicYear)) {
            return null;
        }

        return Borrower::query()
            ->where('type', 'student')
            ->where('nis', $this->nis)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->first();
    }
}
