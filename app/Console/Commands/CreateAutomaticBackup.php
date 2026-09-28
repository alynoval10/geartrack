<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('geartrack:backup')]
#[Description('Membuat backup terjadwal dan salinan di luar server')]
class CreateAutomaticBackup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BackupService $backups): int
    {
        $run = BackupRun::create(['status' => 'running', 'started_at' => now()]);

        try {
            $name = $backups->create((string) config('backup.automatic_creator'), 'automatic');
            $mirror = $backups->mirrorAndPrune($name);
            $run->update([
                'status' => 'success', 'archive_name' => $name, 'mirror_path' => $mirror,
                'message' => $mirror ? 'Backup lokal dan salinan luar server berhasil dibuat.' : 'Backup lokal berhasil; tujuan luar server belum dikonfigurasi.',
                'finished_at' => now(),
            ]);
            $this->info($run->message);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $run->update(['status' => 'failed', 'message' => $exception->getMessage(), 'finished_at' => now()]);
            $this->error('Backup otomatis gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
