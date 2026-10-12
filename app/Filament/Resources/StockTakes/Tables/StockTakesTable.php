<?php

namespace App\Filament\Resources\StockTakes\Tables;

use App\Models\StockTake;
use App\Services\OperationalRecordDeletionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTakesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('name')->label('Sesi')->searchable()->weight('bold'),
            TextColumn::make('location_name')->label('Cakupan')->placeholder('Seluruh lokasi'),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => StockTake::STATUSES[$state] ?? $state),
            TextColumn::make('items_count')->label('Jumlah Aset')->counts('items'),
            TextColumn::make('created_at')->label('Dimulai')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('completed_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('-'),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(StockTake::STATUSES),
        ])->recordActions([
            ViewAction::make()->label('Periksa'),
            Action::make('forceDelete')
                ->label('Hapus Paksa')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus sesi stock opname secara permanen?')
                ->modalDescription('Sesi, hasil pemeriksaan, dan lampiran akan dihapus permanen. Perubahan lokasi atau status aset yang sudah diterapkan serta histori aset tidak dibatalkan.')
                ->modalSubmitActionLabel('Hapus Permanen')
                ->schema([
                    TextInput::make('password')
                        ->label('Kata sandi administrator')
                        ->password()
                        ->required()
                        ->rules(['current_password'])
                        ->autocomplete('current-password'),
                ])
                ->authorize('force-delete-operational-records')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->action(function (StockTake $record, OperationalRecordDeletionService $service): void {
                    $service->deleteStockTake($record);

                    Notification::make()->title('Sesi stock opname berhasil dihapus permanen.')->success()->send();
                }),
        ])
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading('Belum ada sesi stock opname')
            ->emptyStateDescription('Buat sesi berdasarkan lokasi, lalu scan QR aset untuk mencatat hasil pemeriksaan.');
    }
}
