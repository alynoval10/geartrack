<?php

namespace App\Filament\Resources\Borrowers\Tables;

use App\Models\Borrower;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                SelectFilter::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->options(fn (): array => Borrower::query()
                        ->where('type', 'student')
                        ->whereNotNull('academic_year')
                        ->distinct()
                        ->orderBy('academic_year')
                        ->pluck('academic_year', 'academic_year')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan data peminjam terpilih?')
                        ->action(fn (Collection $records) => Borrower::query()
                            ->whereKey($records->modelKeys())
                            ->update(['is_active' => true]))
                        ->successNotificationTitle('Data peminjam berhasil diaktifkan')
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan data peminjam terpilih?')
                        ->modalDescription('Data tetap tersimpan dan dapat diaktifkan kembali nanti.')
                        ->action(fn (Collection $records) => Borrower::query()
                            ->whereKey($records->modelKeys())
                            ->update(['is_active' => false]))
                        ->successNotificationTitle('Data peminjam berhasil dinonaktifkan')
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label('Hapus Data Peminjam')
                        ->modalDescription('Data yang dihapus tidak dapat dipulihkan. Riwayat peminjaman tersimpan dengan data snapshot transaksi.'),
                ])->label('Tindakan'),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Belum ada data peminjam')
            ->emptyStateDescription('Tambahkan Guru/TU dan siswa sebelum membuat peminjaman.');
    }
}
