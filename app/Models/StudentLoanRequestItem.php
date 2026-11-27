<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLoanRequestItem extends Model
{
    use HasFactory;

    protected $fillable = ['student_loan_request_id', 'asset_id', 'asset_code', 'asset_name'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StudentLoanRequest::class, 'student_loan_request_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
