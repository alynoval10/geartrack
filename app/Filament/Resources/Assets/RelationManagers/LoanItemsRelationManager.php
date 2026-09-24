<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\Loans\LoanResource;
use App\Models\LoanItem;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanItems';

    protected static ?string $title = 'Riwayat Peminjaman';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('loan.code')->label('Nomor'),
            TextColumn::make('loan.borrower_name')->label('Peminjam'),
            TextColumn::make('loan.due_date')->label('Batas Kembali')->date('d M Y'),
            TextColumn::make('returned_at')->label('Dikembalikan')->dateTime('d M Y H:i')->placeholder('Masih dipinjam'),
        ])->recordActions([
            Action::make('viewLoan')->label('Lihat Peminjaman')->url(fn (LoanItem $record): string => LoanResource::getUrl('view', ['record' => $record->loan_id])),
        ]);
    }
}
