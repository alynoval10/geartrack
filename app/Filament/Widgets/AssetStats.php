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
    $total = \App\Models\Asset::count();

    $good = \App\Models\Asset::where('condition', 'good')->count();

    $attention = \App\Models\Asset::whereIn(
        'condition',
        ['minor_damage', 'major_damage']
    )->count();

    $borrowed = \App\Models\Asset::where(
        'status',
        'borrowed'
    )->count();

    $goodPercentage = $total > 0
        ? round(($good / $total) * 100)
        : 0;

    return [
        Stat::make('Total Aset', $total)
            ->description('Aset terdaftar di GearTrack')
            ->descriptionIcon('heroicon-m-cube')
            ->color('primary'),

        Stat::make('Kondisi Baik', $good)
            ->description($goodPercentage . '% dari seluruh aset')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success'),

        Stat::make('Perlu Perhatian', $attention)
            ->description('Rusak ringan / berat')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color(
                $attention > 0
                    ? 'warning'
                    : 'gray'
            ),

        Stat::make('Dipinjam', $borrowed)
            ->description('Sedang berada di luar')
            ->descriptionIcon('heroicon-m-arrow-up-right')
            ->color('primary'),
    ];
}

public function getColumnSpan(): int|string|array
{
    return 'full';
}
}