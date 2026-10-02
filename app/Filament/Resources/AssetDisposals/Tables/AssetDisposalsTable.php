<?php

namespace App\Filament\Resources\AssetDisposals\Tables;

use App\Models\AssetDisposal;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetDisposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->weight('bold'),
                TextColumn::make('disposal_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('reason_type')->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => AssetDisposal::REASON_TYPES[$state] ?? $state),
                TextColumn::make('submitted_by_name')->label('Diajukan Oleh')->searchable(),
                TextColumn::make('items_count')->label('Jumlah Aset')->counts('items'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => AssetDisposal::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(AssetDisposal::STATUSES),
                SelectFilter::make('reason_type')->label('Jenis')->options(AssetDisposal::REASON_TYPES),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateIcon('heroicon-o-archive-box-x-mark')
            ->emptyStateHeading('Belum ada pengajuan penghapusan aset')
            ->emptyStateDescription('Ajukan aset rusak berat, hilang permanen, dijual, atau dimusnahkan untuk persetujuan admin.');
    }
}
