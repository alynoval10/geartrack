<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use App\Models\StockTake;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OperationalAlertService
{
    /** @return Builder<Loan> */
    public function overdueLoans(User $user): Builder
    {
        return Loan::query()
            ->where('status', 'open')
            ->whereDate('due_date', '<', today())
            ->when(! $user->isAdmin(), fn (Builder $query): Builder => $query->where('responsible_user_id', $user->id));
    }

    /** @return Builder<MaintenanceSchedule> */
    public function dueMaintenanceSchedules(User $user): Builder
    {
        return MaintenanceSchedule::query()
            ->where('is_active', true)
            ->whereHas('asset')
            ->whereDate('due_date', '<=', today()->addDays(7))
            ->when(! $user->isAdmin(), fn (Builder $query): Builder => $query->where('technician_user_id', $user->id));
    }

    /** @return Builder<StockTake> */
    public function openStockTakes(User $user): Builder
    {
        return StockTake::query()->where('status', 'open');
    }

    /** @return Builder<MaintenanceReport> */
    public function openMaintenanceReports(User $user): Builder
    {
        return MaintenanceReport::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->when(! $user->isAdmin(), function (Builder $query) use ($user): void {
                $query->where(function (Builder $query) use ($user): void {
                    $query->where('reported_by', $user->id)
                        ->orWhereHas('asset', fn (Builder $asset): Builder => $asset->where('custodian_user_id', $user->id));
                });
            });
    }
}
