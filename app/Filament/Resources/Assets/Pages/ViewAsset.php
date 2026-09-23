<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Actions\AssetOperationActions;
use App\Filament\Resources\Assets\AssetResource;
use App\Livewire\AssetHistoryTimeline;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\Url;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    #[Url(as: 'stock_take')]
    public ?int $stockTakeId = null;

    protected function getHeaderActions(): array
    {
        return [
            AssetOperationActions::stockTake($this->record),
            AssetOperationActions::report($this->record),
            Action::make('continueScan')->label('Scan Berikutnya')
                ->url(function (): string {
                    return route('qr.scan', ['stock_take' => $this->stockTakeId]);
                }),
            EditAction::make()
                ->label('Edit Aset'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AssetHistoryTimeline::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->record,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
