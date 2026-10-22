<?php

namespace App\Filament\Resources\StockItems\Tables;

use App\Models\StockItem;
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class StockItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable()->weight('bold'),
                TextColumn::make('name')->label('Nama Barang')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->badge()->placeholder('-'),
                TextColumn::make('location.name')->label('Lokasi')->formatStateUsing(fn (?string $state, StockItem $record): ?string => $record->location?->fullName() ?? $state)->placeholder('-'),
                TextColumn::make('current_stock')->label('Stok')->numeric(decimalPlaces: 3)->suffix(fn (StockItem $record): string => ' '.$record->unit)->sortable(),
                TextColumn::make('minimum_stock')->label('Batas Minimum')->numeric(decimalPlaces: 3)->suffix(fn (StockItem $record): string => ' '.$record->unit),
                TextColumn::make('stock_status')
                    ->label('Kondisi Stok')
                    ->state(fn (StockItem $record): string => $record->isLowStock() ? 'Perlu Restok' : 'Aman')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Perlu Restok' ? 'danger' : 'success'),
                TextColumn::make('is_active')->label('Status')->badge()->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stok perlu restok')
                    ->query(fn ($query) => $query->lowStock()),
                TernaryFilter::make('is_active')->label('Status barang'),
            ])
            ->recordActions([
                Action::make('receive')
                    ->label('Barang Masuk')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (StockItem $record): bool => $record->is_active)
                    ->schema([
                        TextInput::make('quantity')->label('Jumlah Masuk')->numeric()->step('0.001')->minValue(0.001)->maxValue(999999999.999)->rules(['decimal:0,3'])->required(),
                        TextInput::make('unit_price')->label('Harga per Satuan (opsional)')->numeric()->minValue(0)->step('0.01')->rules(['decimal:0,2'])->prefix('Rp'),
                        Textarea::make('notes')->label('Keterangan')->maxLength(2000),
                    ])
                    ->action(function (StockItem $record, array $data, StockMovementService $service): void {
                        $user = auth()->user();
                        if (! $user) {
                            throw ValidationException::withMessages(['quantity' => 'Sesi pengguna tidak ditemukan. Silakan masuk kembali.']);
                        }

                        $unitPrice = filled($data['unit_price'] ?? null) ? (string) $data['unit_price'] : null;
                        $service->record($record, $user, 'in', (string) $data['quantity'], $data['notes'] ?? null, $unitPrice);
                        Notification::make()->title('Stok barang berhasil ditambahkan.')->success()->send();
                    }),
                Action::make('use')
                    ->label('Barang Keluar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn (StockItem $record): bool => $record->is_active)
                    ->schema([
                        TextInput::make('quantity')->label('Jumlah Keluar')->numeric()->step('0.001')->minValue(0.001)->maxValue(999999999.999)->rules(['decimal:0,3'])->required(),
                        Textarea::make('notes')->label('Keperluan / Penerima')->required()->maxLength(2000),
                    ])
                    ->action(function (StockItem $record, array $data, StockMovementService $service): void {
                        $user = auth()->user();
                        if (! $user) {
                            throw ValidationException::withMessages(['quantity' => 'Sesi pengguna tidak ditemukan. Silakan masuk kembali.']);
                        }

                        $service->record($record, $user, 'out', (string) $data['quantity'], $data['notes']);
                        Notification::make()->title('Pengeluaran barang berhasil dicatat.')->success()->send();
                    }),
                ViewAction::make()->label('Riwayat'),
                EditAction::make()->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->emptyStateIcon('heroicon-o-archive-box')
            ->emptyStateHeading('Belum ada barang stok')
            ->emptyStateDescription('Tambahkan data barang habis pakai, lalu catat barang masuk dan keluar untuk menjaga saldo stok tetap akurat.');
    }
}
