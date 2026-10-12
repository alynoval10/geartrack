<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceEntry;
use App\Models\MaintenanceReport;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OperationalRecordDeletionService
{
    public function deleteStockTake(StockTake $stockTake): void
    {
        Gate::authorize('force-delete-operational-records');

        $attachments = DB::transaction(function () use ($stockTake): array {
            $record = StockTake::query()->lockForUpdate()->findOrFail($stockTake->getKey());
            $attachments = $record->items()
                ->lockForUpdate()
                ->get(['attachments'])
                ->flatMap(fn (StockTakeItem $item): array => $item->attachments ?? [])
                ->filter(fn (mixed $path): bool => $this->isEvidencePath($path, 'evidence/stock-takes'))
                ->unique()
                ->values()
                ->all();

            $record->delete();

            return $attachments;
        });

        $this->deleteEvidence($attachments);
    }

    public function deleteMaintenanceReport(MaintenanceReport $maintenanceReport): void
    {
        Gate::authorize('force-delete-operational-records');

        $attachments = DB::transaction(function () use ($maintenanceReport): array {
            $report = MaintenanceReport::query()
                ->lockForUpdate()
                ->findOrFail($maintenanceReport->getKey());
            $entries = $report->entries()->lockForUpdate()->get(['attachments']);
            $attachments = collect($report->attachments ?? [])
                ->merge($entries->flatMap(fn (MaintenanceEntry $entry): array => $entry->attachments ?? []))
                ->filter(fn (mixed $path): bool => $this->isEvidencePath($path, 'evidence/maintenance-reports')
                    || $this->isEvidencePath($path, 'evidence/maintenance-actions'))
                ->unique()
                ->values()
                ->all();

            if ($report->status === 'in_progress' && $report->asset_id) {
                $asset = Asset::query()->lockForUpdate()->find($report->asset_id);

                if ($asset?->status === 'maintenance') {
                    $asset->update([
                        'status' => in_array($report->previous_asset_status, ['available', 'in_use'], true)
                            ? $report->previous_asset_status
                            : 'available',
                    ]);
                }
            }

            $report->delete();

            return $attachments;
        });

        $this->deleteEvidence($attachments);
    }

    private function isEvidencePath(mixed $path, string $directory): bool
    {
        if (! is_string($path) || ! str_starts_with($path, $directory.'/')) {
            return false;
        }

        $filename = substr($path, strlen($directory) + 1);

        return preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]*\z/D', $filename) === 1;
    }

    /** @param list<string> $paths */
    private function deleteEvidence(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                if (! Storage::disk('public')->delete($path)) {
                    report(new RuntimeException('Lampiran catatan operasional tidak dapat dihapus dari penyimpanan.'));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
