<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Models\Loan;
use Filament\Actions\ViewAction;
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
            TextColumn::make('responsible_name')->label('Penanggung Jawab')->searchable(),
            TextColumn::make('items_count')->label('Perangkat')->counts('items'),
            TextColumn::make('due_date')->label('Batas Kembali')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (Loan $record): string => $record->status === 'returned' ? 'Dikembalikan' : ($record->isOverdue() ? 'Terlambat' : 'Dipinjam'))
                ->color(fn (Loan $record): string => $record->status === 'returned' ? 'success' : ($record->isOverdue() ? 'danger' : 'warning')),
        ])->filters([
            SelectFilter::make('status')->label('Status')->options(['open' => 'Dipinjam', 'returned' => 'Dikembalikan']),
            Filter::make('overdue')->label('Terlambat')->query(fn ($query) => $query->where('status', 'open')->whereDate('due_date', '<', today())),
        ])->recordActions([ViewAction::make()])->toolbarActions([]);
    }
}
