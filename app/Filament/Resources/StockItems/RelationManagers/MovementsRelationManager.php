<?php

namespace App\Filament\Resources\StockItems\RelationManagers;

use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Riwayat Barang Masuk dan Keluar';

    protected static ?string $modelLabel = 'Transaksi Stok';

    protected static ?string $pluralModelLabel = 'Riwayat Stok';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('type')->label('Transaksi')->badge()->formatStateUsing(fn (string $state): string => StockMovement::TYPES[$state] ?? $state)->color(fn (string $state): string => $state === 'in' ? 'success' : 'warning'),
                TextColumn::make('quantity')->label('Jumlah')->numeric(decimalPlaces: 3),
                TextColumn::make('stock_before')->label('Stok Sebelum')->numeric(decimalPlaces: 3),
                TextColumn::make('stock_after')->label('Stok Sesudah')->numeric(decimalPlaces: 3),
                TextColumn::make('user.name')->label('Dicatat oleh')->placeholder('Pengguna tidak tersedia'),
                TextColumn::make('notes')->label('Keterangan')->wrap()->placeholder('-'),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada transaksi stok')
            ->emptyStateDescription('Gunakan tombol Barang Masuk atau Barang Keluar pada daftar stok untuk mencatat transaksi.');
    }
}
