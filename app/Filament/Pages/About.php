<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class About extends Page
{
    protected string $view = 'filament.pages.about';

    protected static ?string $title = 'Tentang GearTrack';

    protected static ?string $navigationLabel = 'Tentang';

    protected static string|UnitEnum|null $navigationGroup = 'Akun';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInformationCircle;

    protected static ?int $navigationSort = 100;

    protected function getViewData(): array
    {
        return [
            'version' => config('app.version'),
            'creator' => config('app.creator'),
        ];
    }
}
