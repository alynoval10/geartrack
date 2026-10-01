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
        'qr_label_width_mm',
        'qr_label_height_mm',
    ];

    protected function casts(): array
    {
        return [
            'qr_label_width_mm' => 'integer',
            'qr_label_height_mm' => 'integer',
        ];
    }

    /**
     * GearTrack menggunakan satu profil sekolah untuk seluruh dokumen.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'school_name' => config('app.name', 'GearTrack'),
            'qr_label_width_mm' => 50,
            'qr_label_height_mm' => 30,
        ]);
    }
}
