<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';
}