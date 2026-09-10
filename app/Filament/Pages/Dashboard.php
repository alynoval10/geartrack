<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Ikhtisar';

    public function getHeading(): string
    {
        return 'Ikhtisar';
    }

    public function getSubheading(): ?string
    {
        return 'Pantau kondisi dan keberadaan seluruh aset dalam satu tempat.';
    }
}