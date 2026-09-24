<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SQLite3;
use ZipArchive;

class BackupService
{
    public function __construct(private BackupLock $lock) {}

    public function create(string $creator, string $kind = 'manual'): string
    {
        return $this->lock->run(fn (): string => $this->createArchive($creator, $kind), exclusive: true);
    }

    /** @return list<array{name: string, size: int, created_at: string}> */
    public function archives(): array
    {
        $directory = config('backup.directory');
        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file): bool => preg_match('/^geartrack-[a-z0-9-]+\.zip$/D', $file->getFilename()) === 1)
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->map(fn ($file): array => [
                'name' => $file->getFilename(), 'size' => $file->getSize(),
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])->values()->all();
    }

    public function archivePath(string $name): string
    {
        abort_unless(preg_match('/^geartrack-[a-z0-9-]+\.zip$/D', $name) === 1, 404);
        $path = config('backup.directory').'/'.$name;
        abort_unless(is_file($path) && ! is_link($path), 404);

        return $path;
    }

    public function restore(string $archive, string $creator): string
    {
        return $this->lock->run(function () use ($archive, $creator): string {
            $database = $this->databasePath();
            $work = $this->workspace();
            $newFiles = [];
            try {
                $manifest = $this->unpack($archive, $work);
                $source = $this->openDatabase($work.'/database.sqlite');
                try {
                    $this->verifyDatabase($source);
                    $account = $source->prepare('SELECT COUNT(*) FROM users WHERE lower(email) = :email');
                    $account->bindValue(':email', strtolower($creator));
                    $result = $account->execute();
                    $exists = (int) $result->fetchArray(SQLITE3_NUM)[0] > 0;
                    $result->finalize();
                    $account->close();
                    $this->check($exists, 'Akun admin Anda tidak ada di backup. Pilih backup yang memiliki akun ini.');
                    $current = $this->openDatabase($database, true);
                    try {
                        $this->check($this->schemaHash($source) === $this->schemaHash($current),
                            'Struktur database berbeda. Gunakan backup dari versi GearTrack yang sama.');
                    } finally {
                        $current->close();
                    }
                    $photos = $this->photoPaths($source);
                    $expected = array_map(fn (string $photo): string => 'uploads/'.$photo, $photos);
                    sort($expected);
                    $actual = array_values(array_diff(array_keys($manifest['files']), ['database.sqlite']));
                    sort($actual);
                    $this->check($expected === $actual, 'Foto aset dalam backup tidak lengkap.');

                    $safety = $this->createArchive($creator, 'before-restore');
                    $prefix = 'assets/restored-'.Str::uuid();
                    foreach ($photos as $photo) {
                        $target = $prefix.'/'.hash('sha256', $photo).'.'.pathinfo($photo, PATHINFO_EXTENSION);
                        $stream = fopen($work.'/uploads/'.$photo, 'rb');
                        $newFiles[] = $target;
                        try {
                            if (! Storage::disk('public')->put($target, $stream)) {
                                throw new RuntimeException('Foto backup tidak dapat disimpan.');
                            }
                        } finally {
                            fclose($stream);
                        }
                        $statement = $source->prepare('UPDATE assets SET photo = :target WHERE photo = :original');
                        $statement->bindValue(':target', $target);
                        $statement->bindValue(':original', $photo);
                        $statement->execute()->finalize();
                        $statement->close();
                    }

                    foreach (['sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
                        $source->exec('DELETE FROM "'.$table.'"');
                    }
                    $source->exec('UPDATE users SET remember_token = NULL');
                    $this->verifyDatabase($source);
                    DB::purge();
                    $target = $this->openDatabase($database);
                    try {
                        if (! $source->backup($target)) {
                            throw new RuntimeException('Database gagal dipulihkan.');
                        }
                    } finally {
                        $target->close();
                    }
                    $newFiles = [];

                    return $safety;
                } finally {
                    $source->close();
                }
            } finally {
                foreach ($newFiles as $file) {
                    Storage::disk('public')->delete($file);
                }
                File::deleteDirectory($work);
            }
        }, exclusive: true);
    }

    private function createArchive(string $creator, string $kind): string
    {
        $database = $this->databasePath();
        $work = $this->workspace();
        $name = 'geartrack-'.$kind.'-'.now()->format('Ymd-His').'-'.Str::uuid().'.zip';
        $partial = config('backup.directory').'/'.$name.'.partial';
        $zip = new ZipArchive;
        $opened = false;
        try {
            $source = $this->openDatabase($database, true);
            $snapshot = $this->openDatabase($work.'/database.sqlite');
            try {
                $this->check($source->backup($snapshot), 'Salinan database tidak dapat dibuat.');
                $this->verifyDatabase($snapshot);
                $photos = $this->photoPaths($snapshot);
            } finally {
                $snapshot->close();
                $source->close();
            }

            $this->check($zip->open($partial, ZipArchive::CREATE | ZipArchive::EXCL) === true,
                'Arsip backup tidak dapat dibuat.');
            $opened = true;
            $files = ['database.sqlite' => $work.'/database.sqlite'];
            $missingPhotos = [];
            foreach ($photos as $photo) {
                if ($kind === 'before-restore' && ! Storage::disk('public')->exists($photo)) {
                    $missingPhotos[] = $photo;

                    continue;
                }
                $this->check(Storage::disk('public')->exists($photo),
                    'Foto aset tidak ditemukan: '.$photo.'. Perbaiki foto sebelum membuat backup.');
                $path = Storage::disk('public')->path($photo);
                $root = realpath(Storage::disk('public')->path(''));
                $resolved = realpath($path);
                $this->check($resolved !== false && str_starts_with($resolved, $root.DIRECTORY_SEPARATOR),
                    'Lokasi foto aset tidak valid.');
                $files['uploads/'.$photo] = $path;
            }
            $manifest = [
                'format' => 'geartrack-backup', 'version' => 1,
                'created_at' => now()->toIso8601String(), 'created_by' => $creator,
                'kind' => $kind, 'missing_photos' => $missingPhotos, 'files' => [],
            ];
            $size = 0;
            foreach ($files as $entry => $path) {
                $size += filesize($path);
                $this->check($size <= config('backup.max_unpacked_bytes'), 'Data melebihi batas ukuran backup.');
                $manifest['files'][$entry] = hash_file('sha256', $path);
                $this->check($zip->addFile($path, $entry), 'Berkas gagal ditambahkan ke backup.');
            }
            $this->check(count($files) + 2 <= config('backup.max_entries'), 'Jumlah berkas backup terlalu banyak.');
            $json = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $zip->addFromString('manifest.json', $json);
            $zip->addFromString('signature', hash_hmac('sha256', $json, $this->signingKey()));
            $closed = $zip->close();
            $opened = false;
            $this->check($closed, 'Backup gagal diselesaikan.');
            $this->check(filesize($partial) <= config('backup.max_upload_bytes'),
                'Arsip melebihi batas unggah restore. Kurangi ukuran foto atau naikkan batas backup.');
            $this->check(rename($partial, config('backup.directory').'/'.$name), 'Backup tidak dapat disimpan.');

            return $name;
        } finally {
            if ($opened) {
                $zip->close();
            }
            File::delete($partial);
            File::deleteDirectory($work);
        }
    }

    /** @return array{files: array<string, string>} */
    private function unpack(string $archive, string $work): array
    {
        $this->check(is_file($archive) && filesize($archive) <= config('backup.max_upload_bytes'),
            'Ukuran backup melebihi batas yang diizinkan.');
        $zip = new ZipArchive;
        $this->check($zip->open($archive, ZipArchive::RDONLY) === true, 'File bukan backup ZIP yang valid.');
        try {
            $this->check($zip->numFiles <= config('backup.max_entries'), 'Jumlah berkas backup terlalu banyak.');
            $total = 0;
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                $this->check(! isset($names[$name]) && $this->safeEntry($name), 'Arsip berisi nama berkas yang tidak diizinkan.');
                $names[$name] = true;
                $total += $stat['size'];
                $this->check($total <= config('backup.max_unpacked_bytes'), 'Isi backup terlalu besar.');
            }
            $stat = $zip->statName('manifest.json');
            $this->check($stat !== false && $stat['size'] <= 2 * 1024 * 1024, 'Manifest backup tidak valid.');
            $json = $zip->getFromName('manifest.json');
            $signatureStat = $zip->statName('signature');
            $this->check($signatureStat !== false && $signatureStat['size'] === 64, 'Tanda tangan backup tidak valid.');
            $signature = $zip->getFromName('signature');
            $this->check(is_string($json) && is_string($signature)
                && hash_equals(hash_hmac('sha256', $json, $this->signingKey()), $signature),
                'Backup rusak atau bukan dari instalasi GearTrack ini. APP_KEY harus sama.');
            $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->check(($manifest['format'] ?? null) === 'geartrack-backup'
                && ($manifest['version'] ?? null) === 1 && is_array($manifest['files'] ?? null)
                && isset($manifest['files']['database.sqlite']), 'Format backup tidak didukung.');
            $expected = array_merge(['manifest.json', 'signature'], array_keys($manifest['files']));
            sort($expected);
            $actual = array_keys($names);
            sort($actual);
            $this->check($actual === $expected, 'Isi backup tidak sesuai manifest.');

            foreach ($manifest['files'] as $entry => $hash) {
                $input = $zip->getStream($entry);
                $this->check(is_resource($input), 'Berkas backup tidak dapat dibaca.');
                $path = $work.'/'.$entry;
                File::ensureDirectoryExists(dirname($path), 0700);
                $output = fopen($path, 'wb');
                try {
                    $copied = stream_copy_to_stream($input, $output, config('backup.max_unpacked_bytes') + 1);
                    $this->check($copied === $zip->statName($entry)['size'], 'Ukuran berkas backup tidak sesuai.');
                } finally {
                    fclose($input);
                    fclose($output);
                }
                $this->check(is_string($hash) && hash_equals($hash, hash_file('sha256', $path)), 'Isi backup rusak atau telah diubah.');
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    private function databasePath(): string
    {
        $this->check(class_exists(SQLite3::class) && class_exists(ZipArchive::class), 'Server membutuhkan ekstensi PHP sqlite3 dan zip.');
        $connection = DB::connection();
        $this->check($connection->getDriverName() === 'sqlite' && $connection->getDatabaseName() !== ':memory:',
            'Backup mendukung database SQLite berbasis berkas.');
        $this->check(config('filesystems.disks.public.driver') === 'local', 'Penyimpanan foto harus menggunakan disk lokal.');
        $this->check(config('session.driver') === 'database', 'Restore membutuhkan penyimpanan sesi database.');
        $path = realpath($connection->getDatabaseName());
        $this->check($path !== false && is_file($path), 'Database SQLite tidak ditemukan.');

        return $path;
    }

    private function openDatabase(string $path, bool $readOnly = false): SQLite3
    {
        $database = new SQLite3($path, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $database->enableExceptions(true);
        $database->busyTimeout(5000);

        return $database;
    }

    private function verifyDatabase(SQLite3 $database): void
    {
        $this->check($database->querySingle('PRAGMA integrity_check') === 'ok', 'Database backup rusak.');
        $this->check($database->querySingle('PRAGMA foreign_key_check', true) === [], 'Relasi database backup tidak valid.');
        $this->check((int) $database->querySingle('SELECT COUNT(*) FROM users') > 0, 'Backup harus memiliki akun pengguna.');
    }

    private function schemaHash(SQLite3 $database): string
    {
        $rows = [];
        $result = $database->query("SELECT type, name, tbl_name, sql FROM sqlite_master WHERE name NOT LIKE 'sqlite_%' ORDER BY type, name");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $result->finalize();

        return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    /** @return list<string> */
    private function photoPaths(SQLite3 $database): array
    {
        $paths = [];
        $result = $database->query("SELECT DISTINCT photo FROM assets WHERE photo IS NOT NULL AND photo != '' ORDER BY photo");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->check($this->safePhoto($row['photo']), 'Lokasi foto aset tidak didukung.');
            $paths[] = $row['photo'];
        }
        $result->finalize();

        return $paths;
    }

    private function safePhoto(string $path): bool
    {
        return str_starts_with($path, 'assets/')
            && preg_match('~^assets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|gif|webp|avif|bmp)$~iD', $path) === 1
            && ! str_contains($path, '..');
    }

    private function safeEntry(string $path): bool
    {
        return in_array($path, ['manifest.json', 'signature', 'database.sqlite'], true)
            || (str_starts_with($path, 'uploads/') && $this->safePhoto(substr($path, 8)));
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key');
        $this->check($key !== '', 'APP_KEY belum dikonfigurasi.');

        return $key;
    }

    private function workspace(): string
    {
        $path = config('backup.directory').'/.work-'.Str::uuid();
        File::ensureDirectoryExists($path, 0700);

        return $path;
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['backup' => $message]);
        }
    }
}
