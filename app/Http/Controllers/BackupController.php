<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Backups;
use App\Services\BackupLock;
use App\Services\BackupService;
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
    public function store(Request $request, BackupService $service): RedirectResponse
    {
        Gate::authorize('manage-backups');

        try {
            $name = $service->create($request->user()->email);

            return redirect(Backups::getUrl())->with('backup_status', 'Backup berhasil dibuat: '.$name);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function download(string $name, BackupService $service): BinaryFileResponse
    {
        Gate::authorize('manage-backups');

        return response()->download($service->archivePath($name), $name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $name, BackupService $service, BackupLock $lock): RedirectResponse
    {
        Gate::authorize('manage-backups');
        $request->validate(['password' => ['required', 'current_password']]);
        $lock->run(fn (): bool => File::delete($service->archivePath($name)), exclusive: true);

        return redirect(Backups::getUrl())->with('backup_status', 'File backup dihapus.');
    }

    public function restore(Request $request, BackupService $service): RedirectResponse
    {
        Gate::authorize('manage-backups');
        $request->validate([
            'backup' => ['required', 'file', 'max:'.intdiv(config('backup.max_upload_bytes'), 1024)],
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:RESTORE'],
        ]);

        try {
            $safety = $service->restore($request->file('backup')->getRealPath(), $request->user()->email);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login')
            ->with('backup_status', 'Restore berhasil. Masuk dengan akun dari backup. Backup pengaman: '.$safety);
    }

    private function failure(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof ValidationException || $exception instanceof HttpExceptionInterface) {
            throw $exception;
        }

        report($exception);

        return redirect(Backups::getUrl())->withErrors([
            'backup' => 'Proses backup / restore gagal. Data lama tetap tersedia jika pemulihan database belum berhasil. Periksa log server.',
        ]);
    }
}
