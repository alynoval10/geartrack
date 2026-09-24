<?php

namespace App\Filament\Resources\MaintenanceSchedules\Pages;

use App\Filament\Actions\AssetOperationActions;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use App\Services\MaintenanceScheduleService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceSchedule extends ViewRecord
{
    protected static string $resource = MaintenanceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createReport')->label('Buat Laporan Perawatan')
                ->visible(fn (): bool => $this->record->is_active && $this->record->asset_id !== null
                    && ! $this->record->reports()->whereIn('status', ['open', 'in_progress'])->exists())
                ->requiresConfirmation()->modalDescription('Laporan perawatan akan dibuat. Catat penanganan dan penyelesaiannya untuk memperbarui jadwal berikutnya.')
                ->action(function (Action $action, MaintenanceScheduleService $service): void {
                    $report = AssetOperationActions::run($action, fn () => $service->createReport($this->record, auth()->user()));
                    $action->redirect(MaintenanceReportResource::getUrl('view', ['record' => $report]));
                }),
            EditAction::make(),
        ];
    }
}
