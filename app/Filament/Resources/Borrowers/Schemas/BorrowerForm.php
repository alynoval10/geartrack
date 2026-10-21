<?php

namespace App\Filament\Resources\Borrowers\Schemas;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class BorrowerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Peminjam')
                    ->description('Kelola pilihan Guru/TU dan siswa yang akan digunakan pada transaksi peminjaman.')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Peminjam')
                            ->options(Borrower::TYPES)
                            ->required()
                            ->live()
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === 'staff') {
                                    $set('nis', null);
                                    $set('class_level', null);
                                    $set('class_name', null);
                                    $set('academic_year', null);
                                }
                            }),
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('nis')
                            ->label('NIS')
                            ->visible(fn (Get $get): bool => $get('type') === 'student')
                            ->required(fn (Get $get): bool => $get('type') === 'student')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'student')
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('academic_year', $get('academic_year')),
                            )
                            ->helperText('Nomor Induk Siswa. Tidak perlu diisi untuk Guru/TU.'),
                        Select::make('class_level')
                            ->label('Tingkat')
                            ->options(Borrower::LEVELS)
                            ->live()
                            ->visible(fn (Get $get): bool => $get('type') === 'student')
                            ->required(fn (Get $get): bool => $get('type') === 'student')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'student')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('class_name', null);
                            }),
                        Select::make('class_name')
                            ->label('Kelas')
                            ->options(fn (Get $get): array => self::studentClasses($get('class_level')))
                            ->createOptionForm([
                                TextInput::make('class_name')
                                    ->label('Nama Kelas')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('TJKT 1'),
                            ])
                            ->createOptionUsing(fn (array $data): string => trim($data['class_name']))
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('type') === 'student')
                            ->required(fn (Get $get): bool => $get('type') === 'student')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'student')
                            ->helperText('Pilih tingkat terlebih dahulu. Pilih kelas yang tersedia atau tambahkan nama kelas baru.'),
                        TextInput::make('academic_year')
                            ->label('Tahun Ajaran')
                            ->default(fn (): ?string => SchoolSetting::query()->value('academic_year'))
                            ->visible(fn (Get $get): bool => $get('type') === 'student')
                            ->required(fn (Get $get): bool => $get('type') === 'student')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'student')
                            ->maxLength(30)
                            ->placeholder('2026/2027')
                            ->helperText('Ikuti tahun ajaran aktif di Pengaturan Sekolah. Tambahkan entri baru saat tahun ajaran berganti; transaksi lama tetap menyimpan data sebelumnya.'),
                        TextInput::make('contact')
                            ->label('Kontak (opsional)')
                            ->maxLength(150)
                            ->placeholder('Nomor telepon atau keterangan'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan data yang tidak lagi digunakan. Riwayat transaksi tetap tersimpan.'),
                    ])
                    ->columns(2),
            ]);
    }

    /** @return array<string, string> */
    private static function studentClasses(?string $classLevel): array
    {
        if (! array_key_exists($classLevel ?? '', Borrower::LEVELS)) {
            return [];
        }

        return Borrower::query()
            ->where('type', 'student')
            ->where('class_level', $classLevel)
            ->whereNotNull('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name', 'class_name')
            ->all();
    }
}
