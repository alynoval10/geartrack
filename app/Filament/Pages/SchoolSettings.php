<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SchoolSettings extends Page
{
    protected string $view = 'filament.pages.school-settings';

    protected static ?string $title = 'Pengaturan Sekolah & Laporan';

    protected static ?string $navigationLabel = 'Pengaturan Sekolah';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getViewData(): array
    {
        return ['setting' => SchoolSetting::current()];
    }
}
