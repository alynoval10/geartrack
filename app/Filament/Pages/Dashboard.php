<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AssetStats;
use App\Filament\Widgets\LatestAssets;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header');
    }

    public function getWidgets(): array
    {
        return [
            AssetStats::class,
            LatestAssets::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }
}