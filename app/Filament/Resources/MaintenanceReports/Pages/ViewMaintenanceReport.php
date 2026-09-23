<?php

namespace App\Filament\Resources\MaintenanceReports\Pages;

use App\Filament\Actions\AssetOperationActions;
use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Models\MaintenanceReport;
use App\Services\MaintenanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceReport extends ViewRecord
{
    protected static string $resource = MaintenanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->handlingAction('started', 'Mulai Penanganan')
                ->visible(fn (): bool => $this->record->status === 'open'),
            $this->handlingAction('note', 'Tambah Catatan')
                ->visible(fn (): bool => $this->record->isOpen()),
            $this->handlingAction('resolved', 'Selesaikan')->color('success')
                ->visible(fn (): bool => $this->record->status === 'in_progress'),
            $this->handlingAction('cancelled', 'Batalkan Laporan')->color('gray')
                ->visible(fn (): bool => $this->record->isOpen()),
        ];
    }

    private function handlingAction(string $name, string $label): Action
    {
        $fields = [];
        if ($name === 'started') {
            $fields[] = TextInput::make('technician')->label('Teknisi / Penanggung Jawab')->required()->maxLength(150);
        }
        if ($name === 'resolved') {
            $fields[] = Select::make('condition')->label('Kondisi Akhir')->options(MaintenanceReport::CONDITIONS)->required();
            $fields[] = Select::make('asset_status')->label('Status Aset Setelah Penanganan')
                ->options(['available' => 'Tersedia', 'in_use' => 'Digunakan', 'retired' => 'Nonaktif'])->required();
        }
        $fields[] = Textarea::make('notes')->label('Catatan Tindakan / Alasan')->required()->maxLength(5000)->rows(4);
        if (in_array($name, ['note', 'resolved'], true)) {
            $fields[] = TextInput::make('cost')->label('Biaya Tindakan Ini')->numeric()->minValue(0)
                ->maxValue(9999999999999.99)->prefix('Rp')->default(0)
                ->helperText('Biaya tambahan untuk tindakan ini, bukan total biaya laporan.');
        }

        return Action::make($name)->label($label)->schema($fields)
            ->action(function (array $data, Action $action, MaintenanceService $service) use ($name): void {
                AssetOperationActions::run($action, fn () => $service->update($this->record, $name, $data, auth()->user()));
                $this->record->refresh();
                $this->dispatch('maintenance-updated');
            });
    }
}
