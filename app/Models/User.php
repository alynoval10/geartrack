<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'is_active',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements FilamentUser
{
    /*
    |--------------------------------------------------------------------------
    | ROLE USER
    |--------------------------------------------------------------------------
    |
    | Role yang digunakan di GearTrack.
    |
    | admin = Administrator
    | staff = Petugas
    |
    | Keduanya diperbolehkan login ke panel GearTrack selama akun aktif.
    |
    */
    public const ROLES = [
        'admin' => 'Administrator',
        'staff' => 'Petugas',
    ];

    /*
    |--------------------------------------------------------------------------
    | NILAI DEFAULT
    |--------------------------------------------------------------------------
    |
    | User baru secara default menjadi Petugas dan berstatus aktif.
    |
    | session_version digunakan oleh sistem pengelolaan sesi login.
    |
    */
    protected $attributes = [
        'role' => 'staff',
        'is_active' => true,
        'session_version' => 1,
    ];

    /*
    |--------------------------------------------------------------------------
    | TRAITS
    |--------------------------------------------------------------------------
    |
    | HasFactory  : mendukung factory/testing.
    | Notifiable  : mendukung notification Laravel.
    | SoftDeletes : user tidak langsung dihapus permanen.
    |
    */
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | CEK ADMINISTRATOR
    |--------------------------------------------------------------------------
    |
    | Menghasilkan true hanya jika:
    | - role adalah admin;
    | - akun aktif;
    | - user belum dihapus (soft delete).
    |
    */
    public function isAdmin(): bool
    {
        return $this->role === 'admin'
            && $this->is_active
            && ! $this->trashed();
    }

    /*
    |--------------------------------------------------------------------------
    | AKSES PANEL FILAMENT
    |--------------------------------------------------------------------------
    |
    | Administrator DAN Petugas diperbolehkan login.
    |
    | Syarat:
    | - akun aktif;
    | - akun tidak dihapus;
    | - role terdaftar dalam ROLES.
    |
    | Pembatasan fitur antara Administrator dan Petugas sebaiknya
    | dilakukan melalui Policy / authorization, bukan dengan melarang
    | Petugas login ke panel.
    |
    */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && ! $this->trashed()
            && array_key_exists(
                $this->role,
                self::ROLES
            );
    }

    /*
    |--------------------------------------------------------------------------
    | CAST ATTRIBUTE
    |--------------------------------------------------------------------------
    |
    | Laravel otomatis mengubah tipe data berikut ketika dibaca/ditulis.
    |
    */
    protected function casts(): array
    {
        return [
            // Waktu verifikasi email.
            'email_verified_at' => 'datetime',

            // Password otomatis di-hash Laravel.
            'password' => 'hashed',

            // Status aktif menjadi boolean.
            'is_active' => 'boolean',

            // Versi sesi menjadi integer.
            'session_version' => 'integer',
        ];
    }
}