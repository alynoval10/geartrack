<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Location;
use App\Models\MaintenanceReport;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Services\InventoryReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static ?string $title = 'Laporan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected function getViewData(): array
    {
        return ['types' => InventoryReportService::TYPES, 'locations' => Location::orderBy('name')->pluck('name', 'id'),
            'categories' => Category::orderBy('name')->pluck('name', 'id'), 'stockTakes' => StockTake::latest()->pluck('name', 'id'),
            'conditions' => MaintenanceReport::CONDITIONS, 'statuses' => InventoryReportService::STATUSES, 'results' => StockTakeItem::RESULTS];
    }
}
