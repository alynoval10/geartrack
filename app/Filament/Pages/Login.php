<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Masuk ke GearTrack';
    }

    public function getSubheading(): ?string
    {
        if (session()->has('backup_status')) {
            return session('backup_status');
        }

        return 'Kelola inventaris perangkat TKJ dengan lebih cepat dan terorganisir.';
    }
}
