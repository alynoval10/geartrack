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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
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
}
