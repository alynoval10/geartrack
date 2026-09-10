<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssetStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Aset', Asset::count())
                ->description('Seluruh aset terdaftar')
                ->icon('heroicon-o-cube'),

            Stat::make(
                'Kondisi Baik',
                Asset::where('condition', 'good')->count()
            )
                ->description('Aset dalam kondisi baik')
                ->icon('heroicon-o-check-circle'),

            Stat::make(
                'Perlu Perhatian',
                Asset::whereIn('condition', [
                    'minor_damage',
                    'major_damage',
                ])->count()
            )
                ->description('Aset mengalami kerusakan')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make(
                'Dipinjam',
                Asset::where('status', 'borrowed')->count()
            )
                ->description('Sedang berada di luar')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}