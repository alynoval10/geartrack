<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\Loan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Peminjaman')->schema([
                TextEntry::make('code')->label('Nomor'),
                TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (Loan $record): string => $record->status === 'returned' ? 'Dikembalikan' : ($record->isOverdue() ? 'Terlambat' : 'Dipinjam'))
                    ->color(fn (Loan $record): string => $record->status === 'returned' ? 'success' : ($record->isOverdue() ? 'danger' : 'warning')),
                TextEntry::make('borrower_name')->label('Peminjam'),
                TextEntry::make('borrower_contact')->label('Kelas / Kontak')->placeholder('-'),
                TextEntry::make('responsible_name')->label('Penanggung Jawab'),
                TextEntry::make('package_name')->label('Paket Saat Dipinjam')->placeholder('-'),
                TextEntry::make('borrowed_at')->label('Dipinjam')->dateTime('d M Y H:i'),
                TextEntry::make('due_date')->label('Batas Kembali')->date('d M Y'),
                TextEntry::make('returned_at')->label('Seluruhnya Kembali')->dateTime('d M Y H:i')->placeholder('Belum lengkap'),
                TextEntry::make('creator.name')->label('Petugas'),
                TextEntry::make('purpose')->label('Keperluan')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }
}
