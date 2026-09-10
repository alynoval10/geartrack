<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssetStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = Asset::count();
        $good = Asset::where('condition', 'good')->count();

        $problem = Asset::whereIn('condition', [
            'minor_damage',
            'major_damage',
        ])->count();

        $borrowed = Asset::where('status', 'borrowed')->count();

        $percentage = $total > 0
            ? round(($good / $total) * 100)
            : 0;

        return [
            Stat::make('Total Aset', $total)
                ->description('Aset terdaftar di GearTrack')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Kondisi Baik', $good)
                ->description($percentage . '% dari seluruh aset')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Perlu Perhatian', $problem)
                ->description('Rusak ringan / berat')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($problem > 0 ? 'warning' : 'gray'),

            Stat::make('Dipinjam', $borrowed)
                ->description('Sedang berada di luar')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('info'),
        ];
    }
}