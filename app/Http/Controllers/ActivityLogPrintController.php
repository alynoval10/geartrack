<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogPrintRequest;
use App\Models\AssetHistory;
use App\Models\SchoolSetting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class ActivityLogPrintController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ActivityLogPrintRequest $request): Response
    {
        $filters = $request->validated();
        $histories = AssetHistory::query()
            ->when(
                $filters['from'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $filters['until'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
            )
            ->when(
                $filters['action'] ?? null,
                fn (Builder $query, string $action): Builder => $query->where('action', $action),
            )
            ->when(
                $filters['user'] ?? null,
                fn (Builder $query, int $userId): Builder => $query->where('user_id', $userId),
            )
            ->latest()
            ->get();

        $period = match (true) {
            filled($filters['from'] ?? null) && filled($filters['until'] ?? null) => CarbonImmutable::parse($filters['from'])->format('d/m/Y').'–'.CarbonImmutable::parse($filters['until'])->format('d/m/Y'),
            filled($filters['from'] ?? null) => 'Mulai '.CarbonImmutable::parse($filters['from'])->format('d/m/Y'),
            filled($filters['until'] ?? null) => 'Sampai '.CarbonImmutable::parse($filters['until'])->format('d/m/Y'),
            default => 'Semua tanggal',
        };

        return response()->view('activity-logs.print', [
            'histories' => $histories,
            'period' => $period,
            'school' => SchoolSetting::current(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
