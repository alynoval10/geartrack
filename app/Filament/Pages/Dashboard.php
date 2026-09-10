<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getHeading(): string
    {
        return 'Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Pantau dan kelola inventaris TKJ dalam satu tempat.';
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}