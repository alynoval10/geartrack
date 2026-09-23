<?php

namespace App\Filament\Resources\MaintenanceReports\RelationManagers;

use App\Models\MaintenanceEntry;
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
        ])->emptyStateHeading('Belum ada catatan penanganan');
    }
}
