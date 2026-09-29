<?php

namespace App\Filament\Resources\MaintenanceReports\RelationManagers;

use App\Models\MaintenanceEntry;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Riwayat Penanganan';

    #[On('maintenance-updated')]
    public function refreshEntries(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
            TextColumn::make('action')->label('Tindakan')->badge()->formatStateUsing(fn (string $state): string => MaintenanceEntry::ACTIONS[$state] ?? $state),
            TextColumn::make('notes')->label('Catatan')->wrap(),
            TextColumn::make('cost')->label('Biaya')->money('IDR'),
            TextColumn::make('user.name')->label('Dicatat Oleh')->placeholder('Pengguna dihapus'),
        ])->recordActions([
            Action::make('attachments')
                ->label('Lihat Lampiran')
                ->icon('heroicon-o-paper-clip')
                ->color('gray')
                ->visible(fn (MaintenanceEntry $record): bool => filled($record->attachments))
                ->modalHeading('Lampiran Penanganan')
                ->modalContent(fn (MaintenanceEntry $record) => view('filament.components.evidence-attachments', [
                    'attachments' => $record->attachments,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ])->emptyStateHeading('Belum ada catatan penanganan');
    }
}
