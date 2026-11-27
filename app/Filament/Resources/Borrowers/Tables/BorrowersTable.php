<?php

namespace App\Filament\Resources\Borrowers\Tables;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use App\Models\StudentAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

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
                Action::make('printQr')
                    ->label('Cetak QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('info')
                    ->url(fn (Borrower $record): string => route('borrowers.qr-card', ['token' => $record->qr_token]))
                    ->openUrlInNewTab()
                    ->visible(fn (Borrower $record): bool => $record->type === 'student' && filled($record->qr_token)),
                Action::make('resetStudentPassword')
                    ->label('Atur Ulang Kata Sandi')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Atur ulang kata sandi siswa?')
                    ->modalDescription('Kata sandi akan diatur kembali sama dengan NIS dan siswa wajib menggantinya saat masuk.')
                    ->action(function (Borrower $record): void {
                        $account = StudentAccount::query()->firstOrCreate(
                            ['nis' => $record->nis],
                            ['password' => Hash::make($record->nis), 'must_change_password' => true],
                        );
                        $account->forceFill([
                            'password' => Hash::make($record->nis),
                            'must_change_password' => true,
                        ])->save();

                        Notification::make()->title('Kata sandi siswa diatur ulang')->success()->send();
                    })
                    ->visible(fn (Borrower $record): bool => $record->type === 'student'
                        && $record->is_active
                        && filled($record->nis)
                        && $record->academic_year === SchoolSetting::query()->value('academic_year')),
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
                            ->get()
                            ->each(fn (Borrower $borrower) => $borrower->update(['is_active' => true])))
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
