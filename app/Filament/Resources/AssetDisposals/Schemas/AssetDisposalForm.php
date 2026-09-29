<?php

namespace App\Filament\Resources\AssetDisposals\Schemas;

use App\Models\AssetDisposal;
use App\Services\AssetDisposalEligibility;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetDisposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pengajuan Penghapusan / Pensiun Aset')
                ->description('Aset baru berstatus Pensiun setelah pengajuan disetujui Administrator.')
                ->schema([
                    Select::make('asset_ids')
                        ->label('Daftar Aset')
                        ->multiple()
                        ->options(fn (AssetDisposalEligibility $assets): array => $assets->options())
                        ->getSearchResultsUsing(fn (string $search, AssetDisposalEligibility $assets): array => $assets->options($search))
                        ->getOptionLabelsUsing(fn (array $values, AssetDisposalEligibility $assets): array => $assets->labels($values))
                        ->searchable()
                        ->preload()
                        ->minItems(1)
                        ->maxItems(100)
                        ->required()
                        ->helperText('Pilih maksimal 100 aset yang tidak sedang dipinjam atau diajukan sebelumnya.')
                        ->columnSpanFull(),
                    Select::make('reason_type')
                        ->label('Jenis Penghapusan')
                        ->options(AssetDisposal::REASON_TYPES)
                        ->required(),
                    DatePicker::make('disposal_date')
                        ->label('Tanggal Penghapusan yang Diusulkan')
                        ->default(today())
                        ->required(),
                    Textarea::make('reason')
                        ->label('Alasan / Dasar Penghapusan')
                        ->required()
                        ->maxLength(5000)
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
