<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class Backups extends Page
{
    protected string $view = 'filament.pages.backups';

    protected static ?string $title = 'Backup & Restore';

    protected static ?string $navigationLabel = 'Backup & Restore';

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup =
        'Pengaturan';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    protected function getViewData(): array
    {
        $canManageBackups = Gate::allows('manage-backups');

        return [
            'canManageBackups' => $canManageBackups,

            'backupAccessConfigured' => filled(
                config('backup.admin_emails', [])
            ),

            'archives' => $canManageBackups
                ? app(BackupService::class)->archives()
                : [],

            'maxUploadMb' => intdiv(
                config('backup.max_upload_bytes'),
                1024 * 1024
            ),
        ];
    }
}