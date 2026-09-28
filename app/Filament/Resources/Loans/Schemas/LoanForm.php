<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\AssetSet;
use App\Models\User;
use App\Services\LoanAssetEligibility;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    TextInput::make('borrower_name')->label('Nama Peminjam')->required()->maxLength(150),
                    TextInput::make('borrower_contact')->label('Kelas / Kontak Peminjam')->maxLength(150),
                    Select::make('responsible_user_id')
                        ->label('Penanggung Jawab')
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->default(fn (): ?int => auth()->id())
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
}
