<?php

namespace App\Http\Controllers;

use App\Services\BackupLock;
use App\Services\BackupService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class BackupController extends Controller
{
    /**
     * Membuat backup baru.
     */
    public function store(
        Request $request,
        BackupService $service
    ): RedirectResponse {
        Gate::authorize('manage-backups');

        try {
            $service->create(
                $request->user()->email
            );

            Notification::make()->title('Backup berhasil dibuat')->success()->duration(5000)->send();

            return $this->redirectToBackups();
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    /**
     * Mengunduh file backup.
     */
    public function download(
        string $name,
        BackupService $service
    ): BinaryFileResponse {
        Gate::authorize('manage-backups');

        $path = $service->archivePath($name);

        abort_unless(
            File::exists($path),
            404,
            'File backup tidak ditemukan.'
        );

        return response()->download(
            $path,
            $name,
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Menghapus file backup.
     */
    public function destroy(
        Request $request,
        string $name,
        BackupService $service,
        BackupLock $lock
    ): RedirectResponse {
        Gate::authorize('manage-backups');

        $validator = validator($request->all(), [
            'password' => [
                'required',
                'current_password',
            ],
        ]);

        if ($validator->fails()) {
            return $this->redirectToBackups()->withErrors($validator);
        }

        try {
            $path = $service->archivePath($name);

            if (! File::exists($path)) {
                return $this->redirectToBackups()
                    ->withErrors([
                        'backup' => 'File backup tidak ditemukan atau sudah dihapus.',
                    ]);
            }

            $deleted = $lock->run(
                fn (): bool => File::delete($path),
                exclusive: true
            );

            if (! $deleted) {
                return $this->redirectToBackups()
                    ->withErrors([
                        'backup' => 'File backup gagal dihapus.',
                    ]);
            }

            Notification::make()->title('File backup berhasil dihapus')->success()->duration(5000)->send();

            return $this->redirectToBackups();
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    /**
     * Memulihkan database dari file backup.
     */
    public function restore(
        Request $request,
        BackupService $service
    ): RedirectResponse {
        Gate::authorize('manage-backups');

        $request->validate([
            'backup' => [
                'required',
                'file',
                'max:'.intdiv(
                    config('backup.max_upload_bytes'),
                    1024
                ),
            ],

            'password' => [
                'required',
                'current_password',
            ],

            'confirmation' => [
                'required',
                'in:RESTORE',
            ],
        ]);

        try {
            $backup = $request->file('backup');

            $safety = $service->restore(
                $backup->getRealPath(),
                $request->user()->email
            );
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }

        /*
         * Restore dapat mengubah tabel users/sessions,
         * sehingga sesi lama tidak boleh digunakan lagi.
         */
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('filament.admin.auth.login')
            ->with(
                'backup_status',
                'Restore berhasil. Silakan masuk kembali menggunakan akun dari backup. Backup pengaman: '.$safety
            );
    }

    /**
     * Redirect standar ke halaman Backup & Restore.
     */
    private function redirectToBackups(): RedirectResponse
    {
        return redirect()->route(
            'filament.admin.pages.backups', [], 303
        );
    }

    /**
     * Menangani kegagalan proses backup/restore.
     */
    private function failure(
        Throwable $exception
    ): RedirectResponse {
        /*
         * Error HTTP dan validasi tetap ditangani Laravel
         * secara normal.
         */
        if (
            $exception instanceof ValidationException ||
            $exception instanceof HttpExceptionInterface
        ) {
            throw $exception;
        }

        report($exception);

        return $this->redirectToBackups()
            ->withErrors([
                'backup' => 'Proses backup / restore gagal. '
                    .'Data lama tetap tersedia jika pemulihan database '
                    .'belum berhasil. Periksa log server.',
            ]);
    }
}
