<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Models\User;
use App\Services\OperationalAlertService;
use Filament\Widgets\Widget;

class OperationalAlerts extends Widget
{
    protected string $view = 'filament.widgets.operational-alerts';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $alerts = app(OperationalAlertService::class);

        $overdueLoans = $alerts->overdueLoans($user)->orderBy('due_date')->limit(5)->get();
        $dueSchedules = $alerts->dueMaintenanceSchedules($user)->orderBy('due_date')->limit(5)->get();
        $openStockTakes = $alerts->openStockTakes($user)
            ->withCount(['items', 'items as unchecked_items_count' => fn ($query) => $query->whereNull('checked_at')])
            ->latest('id')
            ->limit(5)
            ->get();
        $openReports = $alerts->openMaintenanceReports($user)->oldest()->limit(5)->get();

        // Hitungan terpisah dari daftar ringkas agar angka tetap menunjukkan seluruh pekerjaan.
        return [
            'cards' => [
                ['label' => 'Pinjaman Terlambat', 'value' => $alerts->overdueLoans($user)->count(), 'url' => LoanResource::getUrl(), 'tone' => 'danger'],
                ['label' => 'Jadwal Perawatan', 'value' => $alerts->dueMaintenanceSchedules($user)->count(), 'url' => MaintenanceScheduleResource::getUrl(), 'tone' => 'warning'],
                ['label' => 'Stock Opname Aktif', 'value' => $alerts->openStockTakes($user)->count(), 'url' => StockTakeResource::getUrl(), 'tone' => 'info'],
                ['label' => 'Laporan Belum Selesai', 'value' => $alerts->openMaintenanceReports($user)->count(), 'url' => MaintenanceReportResource::getUrl(), 'tone' => 'danger'],
            ],
            'overdueLoans' => $overdueLoans,
            'dueSchedules' => $dueSchedules,
            'openStockTakes' => $openStockTakes,
            'openReports' => $openReports,
        ];
    }
}
