<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_name',
        'address',
        'logo',
        'principal_name',
        'principal_nip',
        'asset_manager_name',
        'asset_manager_nip',
        'academic_year',
        'report_signer_name',
        'report_signer_title',
        'report_signer_nip',
    ];

    /**
     * GearTrack menggunakan satu profil sekolah untuk seluruh dokumen.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['school_name' => config('app.name', 'GearTrack')]);
    }
}
