<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Models\Borrower;
use App\Models\Loan;
use App\Services\OperationalRecordDeletionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('code')->label('Nomor')->searchable(),
            TextColumn::make('borrower_name')->label('Peminjam')->searchable(),
            TextColumn::make('borrower_nis')->label('NIS')->placeholder('-')->toggleable(),
            TextColumn::make('borrower_type')->label('Jenis Peminjam')
                ->formatStateUsing(fn (?string $state): string => Borrower::TYPES[$state] ?? '-')
                ->badge()
                ->toggleable(),
            TextColumn::make('borrower_class')->label('Kelas')->placeholder('-')->toggleable(),
            TextColumn::make('borrower_academic_year')->label('Tahun Ajaran')->placeholder('-')->toggleable(),
            TextColumn::make('responsible_name')->label('Penanggung Jawab')->searchable(),
            TextColumn::make('items_count')->label('Perangkat')->counts('items'),
            TextColumn::make('due_date')->label('Batas Kembali')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (Loan $record): string => $record->status === 'returned' ? 'Dikembalikan' : ($record->isOverdue() ? 'Terlambat' : 'Dipinjam'))
                ->color(fn (Loan $record): string => $record->status === 'returned' ? 'success' : ($record->isOverdue() ? 'danger' : 'warning')),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(['open' => 'Dipinjam', 'returned' => 'Dikembalikan']),
            Filter::make('overdue')->label('Terlambat')->query(fn ($query) => $query->where('status', 'open')->whereDate('due_date', '<', today())),
        ])->recordActions([
            ViewAction::make(),
            Action::make('forceDelete')
                ->label('Hapus Paksa')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus transaksi peminjaman secara permanen?')
                ->modalDescription('Transaksi dan rincian perangkat akan dihapus permanen. Aset yang masih berstatus Dipinjam akan dikembalikan ke status sebelumnya. Histori aset tetap tersimpan.')
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
                ->action(function (Loan $record, OperationalRecordDeletionService $service): void {
                    $service->deleteLoan($record, auth()->user());

                    Notification::make()->title('Transaksi peminjaman berhasil dihapus permanen.')->success()->send();
                }),
        ])->toolbarActions([])
            ->emptyStateIcon('heroicon-o-arrow-up-on-square-stack')
            ->emptyStateHeading('Belum ada peminjaman')
            ->emptyStateDescription('Buat peminjaman baru atau scan QR perangkat dari HP untuk memilih aset.');
    }
}
