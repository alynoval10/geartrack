<?php

namespace App\Filament\Resources\Borrowers\Tables;

use App\Models\Borrower;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BorrowersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (?string $state): string => Borrower::TYPES[$state] ?? '-')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'student' ? 'info' : 'gray'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('class_level')
                    ->label('Tingkat')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('class_name')
                    ->label('Kelas')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('contact')
                    ->label('Kontak')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(Borrower::TYPES),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Aktif', 0 => 'Nonaktif']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Belum ada data peminjam')
            ->emptyStateDescription('Tambahkan Guru/TU dan siswa sebelum membuat peminjaman.');
    }
}
