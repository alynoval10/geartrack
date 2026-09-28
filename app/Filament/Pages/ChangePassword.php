<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ChangePassword extends Page
{
    protected string $view = 'filament.pages.change-password';

    protected static ?string $title = 'Ganti Password';

    protected static ?string $navigationLabel = 'Ganti Password';

    protected static string|UnitEnum|null $navigationGroup = 'Akun';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 1;
}
