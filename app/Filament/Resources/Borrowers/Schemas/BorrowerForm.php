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
                                    $set('class_name', null);
                                    $set('academic_year', null);
                                }
                            }),
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('class_name')
                            ->label('Kelas')
                            ->visible(fn (Get $get): bool => $get('type') === 'student')
                            ->required(fn (Get $get): bool => $get('type') === 'student')
                            ->dehydrated(fn (Get $get): bool => $get('type') === 'student')
                            ->maxLength(100)
                            ->helperText('Contoh: X TKJ 1.'),
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
}
