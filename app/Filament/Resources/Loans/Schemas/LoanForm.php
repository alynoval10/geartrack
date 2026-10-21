<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\AssetSet;
use App\Models\Borrower;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\LoanAssetEligibility;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Peminjam')
                ->description('Tentukan peminjam dan guru yang bertanggung jawab atas transaksi ini.')
                ->schema([
                    Select::make('borrower_type')
                        ->label('Jenis Peminjam')
                        ->options(Borrower::TYPES)
                        ->required()
                        ->live()
                        ->columnSpanFull()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('borrower_level', null);
                            $set('borrower_class', null);
                            $set('borrower_id', null);
                        }),
                    Select::make('borrower_level')
                        ->label('Tingkat')
                        ->options(Borrower::LEVELS)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('borrower_type') === 'student')
                        ->required(fn (Get $get): bool => $get('borrower_type') === 'student')
                        ->afterStateUpdated(function (Set $set): void {
                            $set('borrower_class', null);
                            $set('borrower_id', null);
                        }),
                    Select::make('borrower_class')
                        ->label('Kelas')
                        ->options(fn (Get $get): array => self::studentClasses($get('borrower_level')))
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('borrower_type') === 'student')
                        ->required(fn (Get $get): bool => $get('borrower_type') === 'student')
                        ->afterStateUpdated(function (Set $set): void {
                            $set('borrower_id', null);
                        })
                        ->helperText(fn (): string => filled(SchoolSetting::query()->value('academic_year'))
                            ? 'Pilihan kelas mengikuti tingkat dan tahun ajaran aktif. Jika kelas belum tersedia, tambahkan data siswa melalui Sistem → Data Peminjam.'
                            : 'Atur tahun ajaran aktif terlebih dahulu di Pengaturan Sekolah.'),
                    Select::make('borrower_id')
                        ->label(fn (Get $get): string => $get('borrower_type') === 'student' ? 'Nama Siswa' : 'Nama Guru / TU')
                        ->options(fn (Get $get): array => self::borrowerOptions(
                            $get('borrower_type'),
                            $get('borrower_class'),
                            $get('borrower_level'),
                        ))
                        ->searchable()
                        ->preload()
                        ->getOptionLabelUsing(fn (mixed $value): ?string => self::borrowerSelectedLabel($value))
                        ->visible(fn (Get $get): bool => $get('borrower_type') === 'staff'
                            || ($get('borrower_type') === 'student' && filled($get('borrower_class'))))
                        ->required(fn (Get $get): bool => filled($get('borrower_type')))
                        ->helperText(fn (Get $get): ?string => $get('borrower_type') === 'student'
                            ? 'Pilih siswa yang memiliki NIS pada kelas yang sudah dipilih.'
                            : ($get('borrower_type') === 'staff' ? 'Pilih Guru/TU. Kelola daftar melalui Sistem → Data Peminjam.' : null)),
                    Select::make('responsible_user_id')
                        ->label('Penanggung Jawab')
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->when(! (auth()->user()?->isAdmin() ?? false), fn ($query) => $query->whereKey(auth()->id()))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->default(fn (): ?int => auth()->id())
                        ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false))
                        ->dehydrated()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Pilih guru atau administrator aktif yang bertanggung jawab.'),
                    DatePicker::make('due_date')->label('Batas Pengembalian')->default(today()->addDays(7))->minDate(today())->required(),
                    ToggleButtons::make('purpose_template')
                        ->label('Saran Keperluan')
                        ->options([
                            'Praktikum pembelajaran' => 'Praktikum pembelajaran',
                            'Kegiatan ujian kompetensi' => 'Kegiatan ujian kompetensi',
                            'Perawatan dan perbaikan' => 'Perawatan dan perbaikan',
                            'Kegiatan sekolah' => 'Kegiatan sekolah',
                            'Peminjaman sementara' => 'Peminjaman sementara',
                        ])
                        ->inline()
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (filled($state)) {
                                $set('purpose', $state);
                            }
                        }),
                    Textarea::make('purpose')
                        ->label('Keperluan')
                        ->required()
                        ->maxLength(5000)
                        ->helperText('Pilih template di atas atau tulis keperluan secara manual.')
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),

            Section::make('Perangkat yang Dipinjam')
                ->description('Pilih perangkat satuan untuk router atau alat individual. Pilih paket hanya jika seluruh anggota paket dipinjam bersama.')
                ->schema([
                    ToggleButtons::make('selection_type')
                        ->label('Jenis Peminjaman')
                        ->options([
                            'asset' => 'Perangkat Satuan',
                            'package' => 'Paket Perangkat',
                        ])
                        ->default('asset')
                        ->grouped()
                        ->inline()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('asset_ids', []);
                            $set('asset_set_id', null);
                        }),

                    Select::make('asset_ids')
                        ->label('Pilih Perangkat yang Dipinjam')
                        ->multiple()
                        ->options(fn (LoanAssetEligibility $assets): array => $assets->options())
                        ->getSearchResultsUsing(fn (string $search, LoanAssetEligibility $assets): array => $assets->options($search))
                        ->getOptionLabelsUsing(fn (array $values, LoanAssetEligibility $assets): array => $assets->labels($values))
                        ->searchable()
                        ->preload()
                        ->minItems(1)
                        ->maxItems(100)
                        ->required(fn (Get $get): bool => $get('selection_type') === 'asset')
                        ->visible(fn (Get $get): bool => $get('selection_type') === 'asset')
                        ->helperText('Untuk meminjam satu router, pilih router tersebut saja. Daftar hanya menampilkan perangkat yang siap dipinjam.'),

                    Actions::make([
                        Action::make('scanLoanAssets')
                            ->label('Scan QR Perangkat')
                            ->icon('heroicon-o-qr-code')
                            ->color('info')
                            ->modalHeading('Scan QR Perangkat yang Dipinjam')
                            ->modalDescription('Scan satu label QR. Jika berhasil, perangkat ditambahkan dan formulir peminjaman terbuka kembali.')
                            ->modalContent(fn () => view('filament.resources.loans.loan-qr-scanner'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Selesai'),
                    ])
                        ->key('loanAssetScannerActions')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('selection_type') === 'asset'),

                    Select::make('asset_set_id')
                        ->label('Pilih Paket Perangkat')
                        ->options(fn (): array => AssetSet::query()
                            ->where('is_active', true)
                            ->withCount('assets')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (AssetSet $assetSet): array => [
                                $assetSet->id => "{$assetSet->code} — {$assetSet->name} ({$assetSet->assets_count} perangkat)",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('selection_type') === 'package')
                        ->visible(fn (Get $get): bool => $get('selection_type') === 'package')
                        ->helperText('Seluruh perangkat yang menjadi anggota paket akan dipinjam bersama.'),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    /** @return array<string, string> */
    private static function studentClasses(?string $classLevel): array
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
            ->whereNotNull('nis')
            ->where('nis', '!=', '')
            ->whereNotNull('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name', 'class_name')
            ->all();
    }

    /** @return array<int, string> */
    private static function borrowerOptions(?string $type, ?string $className, ?string $classLevel): array
    {
        if (! array_key_exists($type ?? '', Borrower::TYPES)) {
            return [];
        }

        $query = Borrower::query()
            ->where('type', $type)
            ->where('is_active', true);

        if ($type === 'student') {
            $academicYear = SchoolSetting::query()->value('academic_year');

            if (blank($academicYear) || blank($className) || blank($classLevel)) {
                return [];
            }

            $query->where('academic_year', $academicYear)
                ->where('class_name', $className)
                ->where('class_level', $classLevel)
                ->whereNotNull('nis')
                ->where('nis', '!=', '');
        }

        return $query
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Borrower $borrower): array => [
                $borrower->id => self::borrowerLabel($borrower),
            ])
            ->all();
    }

    private static function borrowerSelectedLabel(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $borrower = Borrower::query()->find($value);

        return $borrower ? self::borrowerLabel($borrower) : null;
    }

    private static function borrowerLabel(Borrower $borrower): string
    {
        if ($borrower->type === 'student') {
            return $borrower->name.' — NIS '.$borrower->nis.' — '.trim($borrower->class_level.' '.$borrower->class_name).' ('.$borrower->academic_year.')';
        }

        return $borrower->name;
    }
}
