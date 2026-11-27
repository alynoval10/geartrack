<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use App\Models\Borrower;
use App\Models\SchoolSetting;
use App\Services\ArchiveStudentAcademicYear;
use App\Services\BorrowerImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ListBorrowers extends ListRecords
{
    protected static string $resource = BorrowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('archiveAcademicYear')
                ->label('Arsip Tahun Ajaran')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->disabled(fn (): bool => blank(SchoolSetting::query()->value('academic_year')) || self::sourceAcademicYearOptions() === [])
                ->modalHeading('Arsipkan Data Siswa')
                ->modalDescription(fn (): string => filled($targetAcademicYear = SchoolSetting::query()->value('academic_year'))
                    ? "Tahun ajaran aktif {$targetAcademicYear} akan menjadi tujuan. Siswa kelas X dan XI disiapkan pada tingkat berikutnya, sedangkan kelas XII dinonaktifkan sebagai lulusan. Data tahun sumber dinonaktifkan dan riwayat peminjaman tetap tersimpan."
                    : 'Atur tahun ajaran aktif di Pengaturan Sekolah sebelum mengarsipkan data siswa.')
                ->modalSubmitActionLabel('Arsipkan Siswa')
                ->requiresConfirmation()
                ->form([
                    Select::make('source_academic_year')
                        ->label('Tahun Ajaran Sumber')
                        ->options(fn (): array => self::sourceAcademicYearOptions())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data, ArchiveStudentAcademicYear $archive): void {
                    $result = $archive->handle($data['source_academic_year']);

                    Notification::make()
                        ->title('Arsip tahun ajaran selesai')
                        ->body("{$result['archived']} data siswa sumber dinonaktifkan; {$result['promoted']} disiapkan untuk tahun ajaran aktif, termasuk {$result['updated']} data tujuan yang sudah ada; {$result['graduated']} siswa kelas XII diproses sebagai lulusan.")
                        ->success()
                        ->duration(8000)
                        ->send();
                }),
            Action::make('downloadTemplate')
                ->label('Unduh Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('borrowers.template')),
            Action::make('printStudentQrs')
                ->label('Cetak QR Siswa')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->modalHeading('Cetak QR Siswa per Tingkat atau Rombel')
                ->modalDescription(fn (): string => filled($academicYear = SchoolSetting::query()->value('academic_year'))
                    ? "QR dibuat untuk siswa aktif tahun ajaran {$academicYear}."
                    : 'Tahun ajaran aktif belum diatur. Atur tahun ajaran di Pengaturan Sekolah sebelum mencetak QR siswa.')
                ->modalSubmitActionLabel('Tampilkan QR')
                ->form([
                    Select::make('class_level')
                        ->label('Tingkat')
                        ->options(Borrower::LEVELS)
                        ->required()
                        ->live(),
                    ToggleButtons::make('scope')
                        ->label('Cakupan')
                        ->options([
                            'level' => 'Semua Rombel pada Tingkat Ini',
                            'class' => 'Satu Rombel',
                        ])
                        ->default('level')
                        ->inline()
                        ->live()
                        ->required(),
                    Select::make('class_name')
                        ->label('Kelas / Rombel')
                        ->options(fn (Get $get): array => self::activeStudentClasses($get('class_level')))
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('scope') === 'class')
                        ->required(fn (Get $get): bool => $get('scope') === 'class')
                        ->helperText('Daftar hanya memuat rombel dengan siswa aktif pada tahun ajaran berjalan.'),
                ])
                ->action(function (array $data, Action $action): void {
                    $action->redirect(route('borrowers.qr-cards', array_filter([
                        'level' => $data['class_level'],
                        'class_name' => $data['scope'] === 'class' ? $data['class_name'] : null,
                    ], fn (mixed $value): bool => filled($value))));
                }),
            Action::make('printStudentLogins')
                ->label('Cetak Akun Login Siswa')
                ->icon('heroicon-o-identification')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Cetak Akun Login Siswa')
                ->modalDescription('Cetak daftar nama dan NIS sebagai username untuk siswa aktif pada tahun ajaran berjalan. Kata sandi saat ini tidak ditampilkan.')
                ->modalSubmitActionLabel('Tampilkan Daftar')
                ->form([
                    Select::make('class_level')
                        ->label('Tingkat')
                        ->options(Borrower::LEVELS)
                        ->required()
                        ->live(),
                    ToggleButtons::make('scope')
                        ->label('Cakupan')
                        ->options([
                            'level' => 'Semua Rombel pada Tingkat Ini',
                            'class' => 'Satu Rombel',
                        ])
                        ->default('level')
                        ->inline()
                        ->live()
                        ->required(),
                    Select::make('class_name')
                        ->label('Kelas / Rombel')
                        ->options(fn (Get $get): array => self::activeStudentClasses($get('class_level')))
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('scope') === 'class')
                        ->required(fn (Get $get): bool => $get('scope') === 'class'),
                ])
                ->action(function (array $data, Action $action): void {
                    $action->redirect(route('borrowers.student-logins', array_filter([
                        'level' => $data['class_level'],
                        'class_name' => $data['scope'] === 'class' ? $data['class_name'] : null,
                    ], fn (mixed $value): bool => filled($value))));
                }),
            Action::make('import')
                ->label('Impor Data')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/zip',
                        ])
                        ->maxSize(5120)
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Maksimal 1.000 baris dan 5 MB. Gunakan sheet Data Peminjam dari template GearTrack.'),
                ])
                ->action(function (array $data): void {
                    $path = $data['file']->getRealPath();
                    if (! is_string($path)) {
                        throw ValidationException::withMessages(['file' => 'File yang diunggah tidak dapat dibaca.']);
                    }

                    $result = app(BorrowerImportService::class)->import($path);
                    Notification::make()
                        ->title($result['created'].' data ditambahkan, '.$result['updated'].' data diperbarui')
                        ->success()
                        ->duration(6000)
                        ->send();
                }),
            CreateAction::make()->label('Tambah Data Peminjam'),
        ];
    }

    /** @return array<string, string> */
    private static function sourceAcademicYearOptions(): array
    {
        $targetAcademicYear = SchoolSetting::query()->value('academic_year');

        return Borrower::query()
            ->where('type', 'student')
            ->where('is_active', true)
            ->whereNotNull('academic_year')
            ->when(filled($targetAcademicYear), fn (Builder $query): Builder => $query->where('academic_year', '!=', $targetAcademicYear))
            ->distinct()
            ->orderBy('academic_year')
            ->pluck('academic_year', 'academic_year')
            ->all();
    }

    /** @return array<string, string> */
    private static function activeStudentClasses(?string $classLevel): array
    {
        $academicYear = SchoolSetting::query()->value('academic_year');

        if (blank($academicYear) || ! array_key_exists($classLevel ?? '', Borrower::LEVELS)) {
            return [];
        }

        return Borrower::query()
            ->where('type', 'student')
            ->where('is_active', true)
            ->where('academic_year', $academicYear)
            ->where('class_level', $classLevel)
            ->whereNotNull('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name', 'class_name')
            ->all();
    }
}
