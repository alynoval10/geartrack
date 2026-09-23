<?php

namespace App\Filament\Actions;

use App\Filament\Resources\MaintenanceReports\MaintenanceReportResource;
use App\Filament\Resources\MaintenanceReports\Schemas\MaintenanceReportForm;
use App\Filament\Schemas\StockTakeResultFields;
use App\Models\Asset;
use App\Models\StockTake;
use App\Services\MaintenanceService;
use App\Services\StockTakeService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class AssetOperationActions
{
    public static function stockTake(Asset $asset): Action
    {
        return Action::make('stockTake')->label('Catat Stock Opname')->icon('heroicon-o-qr-code')
            ->modalHeading('Pemeriksaan: '.$asset->asset_code)
            ->fillForm(fn (array $arguments): array => ['stock_take_id' => $arguments['stock_take_id'] ?? null, 'result' => 'found'])
            ->schema([
                Select::make('stock_take_id')->label('Sesi Stock Opname')
                    ->options(fn (): array => StockTake::where('status', 'open')
                        ->whereHas('items', fn ($query) => $query->where('asset_id', $asset->id))
                        ->latest('id')->pluck('name', 'id')->all())
                    ->searchable()->required()
                    ->helperText('Hanya sesi aktif yang mencakup aset ini. Buat sesi dari menu Stock Opname jika belum ada.'),
                ...StockTakeResultFields::make(),
            ])
            ->action(function (array $data, Action $action, StockTakeService $service, $livewire) use ($asset): void {
                self::run($action, function () use ($data, $asset, $service): void {
                    $session = StockTake::find($data['stock_take_id']);
                    $item = $session?->items()->where('asset_id', $asset->id)->first();
                    if (! $session || ! $item) {
                        throw ValidationException::withMessages(['stock_take_id' => 'Aset tidak termasuk dalam sesi ini.']);
                    }
                    $service->record($session, $item, $data, auth()->user());
                });
                $livewire->stockTakeId = (int) $data['stock_take_id'];
                $livewire->record->refresh();
            });
    }

    public static function report(Asset $asset): Action
    {
        return Action::make('reportMaintenance')->label('Laporkan Kerusakan / Perawatan')
            ->icon('heroicon-o-wrench-screwdriver')
            ->schema(MaintenanceReportForm::fields())
            ->action(function (array $data, Action $action, MaintenanceService $service) use ($asset): void {
                $report = self::run($action, fn () => $service->report($asset, $data, auth()->user()));
                $action->redirect(MaintenanceReportResource::getUrl('view', ['record' => $report]));
            });
    }

    public static function run(Action $action, Closure $operation): mixed
    {
        try {
            $result = $operation();
        } catch (ValidationException $exception) {
            Notification::make()->title('Belum dapat disimpan')
                ->body(collect($exception->errors())->flatten()->implode(' '))->danger()->send();
            $action->halt();
        }

        Notification::make()->title('Berhasil disimpan')->success()->send();

        return $result;
    }
}
