<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ImportAssets extends Page
{
    protected string $view = 'filament.pages.import-assets';

    protected static ?string $title = 'Impor Aset';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected function getViewData(): array
    {
        $draft = session('asset_import');
        if (! is_array($draft) || $draft['user_id'] !== auth()->id() || $draft['expires_at'] < now()->timestamp) {
            $draft = null;
        }

        return ['draft' => $draft];
    }
}
