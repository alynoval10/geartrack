<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Masuk ke GearTrack';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola inventaris perangkat TKJ dengan lebih cepat dan terorganisir.';
    }
}