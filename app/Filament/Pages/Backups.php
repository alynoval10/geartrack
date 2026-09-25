<?php

namespace App\Filament\Pages;

use App\Services\BackupLock;
use App\Services\BackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
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

    public function deleteBackupAction(): Action
    {
        return Action::make('deleteBackup')
            ->label('Hapus backup')->color('danger')->size('sm')
            ->authorize('manage-backups')
            ->modalHeading('Hapus file backup?')
            ->modalDescription(fn (array $arguments): string => 'File '.$arguments['name'].' akan dihapus. Data inventaris tetap tersedia.')
            ->modalSubmitActionLabel('Hapus File Ini')
            ->schema([
                TextInput::make('password')->label('Kata sandi akun Anda')
                    ->password()->required()->rules(['current_password'])->autocomplete('current-password'),
            ])
            ->action(function (array $arguments, BackupService $service, BackupLock $lock): void {
                Gate::authorize('manage-backups');

                try {
                    $deleted = $lock->run(fn (): bool => File::delete($service->archivePath((string) ($arguments['name'] ?? ''))));
                    if (! $deleted) {
                        Notification::make()->title('File backup gagal dihapus')->danger()->send();

                        return;
                    }
                } catch (Throwable $exception) {
                    if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 404) {
                        Notification::make()->title('File backup sudah tidak tersedia')->warning()->send();

                        return;
                    }

                    report($exception);
                    Notification::make()->title('File backup gagal dihapus. Coba lagi.')->danger()->send();

                    return;
                }

                Notification::make()->title('File backup berhasil dihapus')->success()->duration(5000)->send();
            });
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
