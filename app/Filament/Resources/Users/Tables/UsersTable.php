<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\UserManagementService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'admin' => 'Administrator',
                            'guru' => 'Guru',
                            default => $state ?: '-',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'admin' => 'primary',
                            'guru' => 'gray',
                            default => 'gray',
                        }
                    ),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(User::ROLES),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->using(fn (User $record): bool => app(UserManagementService::class)->delete($record)),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Tambahkan akun guru atau administrator agar tanggung jawab aset dapat ditetapkan.');
    }
}
