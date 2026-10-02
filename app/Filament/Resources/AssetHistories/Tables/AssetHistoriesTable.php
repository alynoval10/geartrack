<?php

namespace App\Filament\Resources\AssetHistories\Tables;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetHistory;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['asset', 'user']))
            ->headerActions([
                Action::make('print')
                    ->label('Cetak Log')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(function (HasTable $livewire): string {
                        $dateFilter = $livewire->getTableFilterState('created_at') ?? [];
                        $actionFilter = $livewire->getTableFilterState('action') ?? [];
                        $userFilter = $livewire->getTableFilterState('user') ?? [];

                        // Teruskan filter tabel agar dokumen cetak berisi data yang sedang diperiksa.
                        return route('activity-logs.print', array_filter([
                            'from' => $dateFilter['from'] ?? null,
                            'until' => $dateFilter['until'] ?? null,
                            'action' => $actionFilter['value'] ?? null,
                            'user' => $userFilter['value'] ?? null,
                        ], fn (mixed $value): bool => filled($value)));
                    })
                    ->openUrlInNewTab(),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label('Pengguna')
                    ->getStateUsing(fn (AssetHistory $record): string => $record->user_name ?: 'Sistem')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Aktivitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AssetHistory::ACTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'deleted' => 'danger',
                        'transfer', 'stock_take' => 'info',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn (AssetHistory $record): ?string => $record->asset
                        ? AssetResource::getUrl('view', ['record' => $record->asset])
                        : null),

                TextColumn::make('asset_name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('old_value')
                    ->label('Sebelum')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('new_value')
                    ->label('Sesudah')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('Dari '.date('d/m/Y', strtotime($data['from'])))
                                ->removeField('from');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('Sampai '.date('d/m/Y', strtotime($data['until'])))
                                ->removeField('until');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('action')
                    ->label('Aktivitas')
                    ->options(AssetHistory::ACTIONS),

                SelectFilter::make('user')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('Belum ada aktivitas yang tercatat')
            ->emptyStateDescription('Aktivitas aset akan muncul setelah pengguna menambah, mengubah, meminjam, memindahkan, atau memeriksa aset.');
    }
}
