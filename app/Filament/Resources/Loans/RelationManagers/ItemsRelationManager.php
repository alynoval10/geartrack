<?php

namespace App\Filament\Resources\Loans\RelationManagers;

use App\Filament\Actions\AssetOperationActions;
use App\Models\LoanItem;
use App\Models\MaintenanceReport;
use App\Services\LoanService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Perangkat & Pengembalian';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset_code')->label('Kode'),
            TextColumn::make('asset_name')->label('Perangkat'),
            TextColumn::make('condition_before')->label('Kondisi Awal')->formatStateUsing(fn (string $state): string => MaintenanceReport::CONDITIONS[$state] ?? $state),
            TextColumn::make('condition_after')->label('Kondisi Kembali')->formatStateUsing(fn (string $state): string => MaintenanceReport::CONDITIONS[$state] ?? $state)->placeholder('-'),
            TextColumn::make('returned_at')->label('Dikembalikan')->dateTime('d M Y H:i')->placeholder('Masih dipinjam'),
            TextColumn::make('receiver.name')->label('Penerima')->placeholder('-'),
            TextColumn::make('return_notes')->label('Catatan')->wrap(),
        ])->recordActions([
            Action::make('receive')->label('Terima Pengembalian')->visible(fn (LoanItem $record): bool => $record->returned_at === null)
                ->schema([
                    Select::make('condition')->label('Kondisi Saat Diterima')->options(MaintenanceReport::CONDITIONS)->required()->default('good'),
                    Textarea::make('notes')->label('Catatan / Kerusakan')->maxLength(5000)->helperText('Wajib diisi jika rusak. Laporan kerusakan dibuat otomatis.'),
                ])->action(function (LoanItem $record, array $data, Action $action, LoanService $service): void {
                    AssetOperationActions::run($action, fn () => $service->receive($this->getOwnerRecord(), $record, $data, auth()->user()));
                    $this->dispatch('loan-updated');
                }),
        ]);
    }
}
