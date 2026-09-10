<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestAssets extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aset Terbaru')
            ->description('Perangkat yang terakhir ditambahkan ke GearTrack.')
            ->query(
                fn (): Builder => Asset::query()
                    ->with(['category', 'location'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->placeholder('-'),

                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'good' => 'Baik',
                        'minor_damage' => 'Rusak Ringan',
                        'major_damage' => 'Rusak Berat',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'good' => 'success',
                        'minor_damage' => 'warning',
                        'major_damage' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }

    public function getColumnSpan(): int|string|array
{
    return 'full';
}
}