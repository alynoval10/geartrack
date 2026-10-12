<?php

namespace App\Filament\Resources\MaintenanceReports\Tables;

use App\Models\MaintenanceReport;
use App\Services\OperationalRecordDeletionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('title')->label('Laporan')->searchable()->weight('bold'),
            TextColumn::make('asset_code')->label('Kode Aset')->searchable(),
            TextColumn::make('asset_name')->label('Nama Aset')->searchable(),
            TextColumn::make('type')->label('Jenis')->formatStateUsing(fn (string $state): string => MaintenanceReport::TYPES[$state] ?? $state),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => MaintenanceReport::STATUSES[$state] ?? $state)
                ->color(fn (string $state): string => match ($state) {
                    'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', default => 'gray',
                }),
            TextColumn::make('technician')->label('Teknisi')->placeholder('-'),
            TextColumn::make('created_at')->label('Dilaporkan')->dateTime('d M Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(MaintenanceReport::STATUSES),
            SelectFilter::make('type')->label('Jenis')->options(MaintenanceReport::TYPES),
        ])->recordActions([
            ViewAction::make()->label('Tangani'),
            Action::make('forceDelete')
                ->label('Hapus Paksa')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus laporan secara permanen?')
                ->modalDescription('Laporan, catatan penanganan, dan semua lampiran akan dihapus permanen. Histori aset tetap disimpan. Jika penanganan sedang berlangsung, status aset dikembalikan bila aset masih berstatus Dalam Perawatan.')
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
                ->action(function (MaintenanceReport $record, OperationalRecordDeletionService $service): void {
                    $service->deleteMaintenanceReport($record);

                    Notification::make()->title('Laporan berhasil dihapus permanen.')->success()->send();
                }),
        ])
            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->emptyStateHeading('Belum ada laporan kerusakan atau perawatan')
            ->emptyStateDescription('Gunakan scan QR atau pilih aset secara manual untuk membuat laporan pertama.');
    }
}
