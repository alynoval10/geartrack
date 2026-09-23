<?php

namespace App\Filament\Resources\StockTakes\Pages;

use App\Filament\Actions\AssetOperationActions;
use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Services\StockTakeService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewStockTake extends ViewRecord
{
    protected static string $resource = StockTakeResource::class;

    #[On('stock-take-updated')]
    public function refreshSummary(): void
    {
        $this->record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')->label('Scan QR')->icon('heroicon-o-qr-code')
                ->url(fn (): string => route('qr.scan', ['stock_take' => $this->record->id]))
                ->visible(fn (): bool => $this->record->status === 'open'),
            Action::make('complete')->label('Selesaikan Sesi')->color('success')
                ->requiresConfirmation()->modalDescription('Semua aset harus sudah diperiksa. Hasil sesi yang selesai akan dikunci.')
                ->visible(fn (): bool => $this->record->status === 'open')
                ->action(function (Action $action, StockTakeService $service): void {
                    AssetOperationActions::run($action, fn () => $service->complete($this->record));
                    $this->record->refresh();
                }),
        ];
    }
}
