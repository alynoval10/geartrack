<?php

namespace App\Filament\Resources\StockTakes\RelationManagers;

use App\Filament\Actions\AssetOperationActions;
use App\Filament\Schemas\StockTakeResultFields;
use App\Models\StockTakeItem;
use App\Services\StockTakeService;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Daftar Pemeriksaan';

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('asset_code')->defaultSort('asset_code')
            ->columns([
                TextColumn::make('asset_code')->label('Kode Aset')->searchable(),
                TextColumn::make('asset_name')->label('Nama Aset')->searchable(),
                TextColumn::make('expected_location_name')->label('Lokasi Awal')->placeholder('Belum ditentukan'),
                TextColumn::make('result')->label('Hasil')->badge()
                    ->formatStateUsing(fn (string $state): string => StockTakeItem::RESULTS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'found' => 'success', 'missing' => 'danger', 'moved' => 'warning', default => 'gray',
                    }),
                TextColumn::make('observed_location_name')->label('Lokasi Aktual')->placeholder('-'),
                TextColumn::make('checker.name')->label('Petugas')->placeholder('-'),
                TextColumn::make('checked_at')->label('Diperiksa')->dateTime('d M Y H:i')->placeholder('-'),
                TextColumn::make('notes')->label('Catatan')->wrap()->limit(100)->toggleable(),
            ])->filters([
                SelectFilter::make('result')->label('Hasil Pemeriksaan')->options(StockTakeItem::RESULTS),
            ])->recordActions([
                Action::make('recordResult')->label('Catat Hasil')->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === 'open')
                    ->fillForm(fn (StockTakeItem $record): array => [
                        'result' => $record->result === 'pending' ? 'found' : $record->result,
                        'observed_location_id' => $record->observed_location_id,
                        'notes' => $record->notes,
                    ])
                    ->schema(StockTakeResultFields::make())
                    ->action(function (StockTakeItem $record, array $data, Action $action, StockTakeService $service): void {
                        AssetOperationActions::run($action, fn () => $service->record($this->getOwnerRecord(), $record, $data, auth()->user()));
                        $this->dispatch('stock-take-updated');
                    }),
                Action::make('qr')->label('Lihat QR')->icon('heroicon-o-qr-code')
                    ->visible(fn (StockTakeItem $record): bool => $record->asset_id !== null)
                    ->url(fn (StockTakeItem $record): string => route('asset.qr.show', ['token' => $record->asset->qr_token, 'stock_take' => $record->stock_take_id])),
            ])->modifyQueryUsing(fn ($query) => $query->with('asset'));
    }
}
