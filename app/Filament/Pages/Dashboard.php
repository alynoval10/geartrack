<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AssetStats;
use App\Filament\Widgets\LatestAssets;
use App\Filament\Widgets\MaintenanceReminders;
use App\Filament\Widgets\OperationalAlerts;
use App\Filament\Widgets\UserTasks;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 1;

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header');
    }

    public function getWidgets(): array
    {
        return [
            UserTasks::class,
            OperationalAlerts::class,
            AssetStats::class,
            MaintenanceReminders::class,
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
