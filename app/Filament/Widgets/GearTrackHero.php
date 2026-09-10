<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GearTrackHero extends Widget
{
    protected string $view = 'filament.widgets.gear-track-hero';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 4,
    ];
}