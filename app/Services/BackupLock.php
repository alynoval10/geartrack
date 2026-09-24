<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BackupLock
{
    private ?bool $exclusive = null;

    public function run(Closure $callback, bool $exclusive = false): mixed
    {
        if ($this->exclusive !== null) {
            if ($exclusive && ! $this->exclusive) {
                throw new HttpException(503, 'Operasi backup tidak dapat dimulai dari permintaan ini.');
            }

            return $callback();
        }

        File::ensureDirectoryExists(config('backup.directory'), 0700);
        $handle = fopen(config('backup.directory').'/.operation.lock', 'c');
        if ($handle === false) {
            throw new HttpException(503, 'Penyimpanan backup tidak dapat dibuka.');
        }
        try {
            if (! flock($handle, ($exclusive ? LOCK_EX : LOCK_SH) | LOCK_NB)) {
                throw new HttpException(503, 'Backup atau pemulihan sedang berjalan. Coba lagi sebentar.');
            }

            $this->exclusive = $exclusive;

            return $callback();
        } finally {
            $this->exclusive = null;
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
