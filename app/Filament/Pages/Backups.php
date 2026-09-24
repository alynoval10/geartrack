<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class Backups extends Page
{
    protected string $view = 'filament.pages.backups';

    protected static ?string $title = 'Backup & Restore';

    protected static ?string $navigationLabel = 'Backup & Restore';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    public static function canAccess(): bool
    {
        return Gate::allows('manage-backups');
    }

    protected function getViewData(): array
    {
        return [
            'archives' => app(BackupService::class)->archives(),
            'maxUploadMb' => intdiv(config('backup.max_upload_bytes'), 1024 * 1024),
        ];
    }
}
