<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    Textarea::make('purpose')->label('Keperluan')->required()->maxLength(5000)->columnSpanFull(),
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
                        ->options(fn (): array => self::assetOptions())
                        ->getSearchResultsUsing(fn (string $search): array => self::assetOptions($search))
                        ->getOptionLabelsUsing(fn (array $values): array => Asset::query()
                            ->with(['category', 'location'])
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => self::assetLabel($asset)])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->minItems(1)
                        ->maxItems(100)
                        ->required(fn (Get $get): bool => $get('selection_type') === 'asset')
                        ->visible(fn (Get $get): bool => $get('selection_type') === 'asset')
                        ->helperText('Untuk meminjam satu router, pilih router tersebut saja. Daftar hanya menampilkan perangkat yang siap dipinjam.'),

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

    /**
     * Hanya tampilkan perangkat yang memenuhi syarat peminjaman saat ini.
     *
     * @return array<int, string>
     */
    private static function assetOptions(?string $search = null): array
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->whereIn('status', ['available', 'in_use'])
            ->where('condition', 'good')
            ->whereDoesntHave('loanItems', fn (Builder $query): Builder => $query->whereNotNull('active_asset_id'))
            ->whereDoesntHave('maintenanceReports', fn (Builder $query): Builder => $query->whereIn('status', ['open', 'in_progress']))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('asset_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('asset_code')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => self::assetLabel($asset)])
            ->all();
    }

    private static function assetLabel(Asset $asset): string
    {
        return collect([
            $asset->asset_code.' — '.$asset->name,
            $asset->category?->name,
            $asset->location?->name,
        ])->filter()->implode(' | ');
    }
}
