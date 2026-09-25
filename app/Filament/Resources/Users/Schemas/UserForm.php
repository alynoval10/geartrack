<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi User')
                    ->description(
                        'Kelola akun pengguna yang dapat mengakses GearTrack dan menjadi penanggung jawab aset.'
                    )
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'admin' => 'Administrator',
                                'staff' => 'Petugas',
                            ])
                            ->default('staff')
                            ->required()
                            ->native(false),

                        Toggle::make('is_active')
                            ->label('User Aktif')
                            ->helperText(
                                'User nonaktif tidak dapat digunakan sebagai penanggung jawab baru.'
                            )
                            ->default(true),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(
                                fn (?string $state): bool => filled($state)
                            )
                            ->dehydrateStateUsing(
                                fn (string $state): string => Hash::make($state)
                            )
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->helperText(
                                'Saat mengubah user, kosongkan jika password tidak ingin diganti.'
                            ),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->minLength(8),
                    ])
                    ->columns(2),
            ]);
    }
}