<?php

namespace App\Services;

use App\Models\Borrower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;
use ZipArchive;

class BorrowerImportService
{
    public const HEADERS = ['jenis_peminjam', 'nama', 'kelas', 'tahun_ajaran', 'kontak', 'aktif'];

    public const MAX_ROWS = 1000;

    /** @return array<string, iterable<list<string>>> */
    public function template(): array
    {
        return [
            'Data Peminjam' => [self::HEADERS],
            'Petunjuk' => [
                ['Kolom', 'Cara mengisi'],
                ['jenis_peminjam', 'Wajib. Isi Guru/TU atau Siswa.'],
                ['nama', 'Wajib. Nama lengkap, maksimal 150 karakter.'],
                ['kelas', 'Wajib untuk Siswa. Kosongkan untuk Guru/TU.'],
                ['tahun_ajaran', 'Wajib untuk Siswa, contoh 2026/2027. Kosongkan untuk Guru/TU.'],
                ['kontak', 'Opsional. Nomor telepon atau keterangan, maksimal 150 karakter.'],
                ['aktif', 'Opsional. Isi Aktif atau Nonaktif. Kosong berarti Aktif.'],
                ['Batas', 'Maksimal 1.000 baris data dan ukuran file 5 MB. Jangan mengubah urutan/judul kolom atau memakai rumus.'],
                ['Penyimpanan', 'Data dengan jenis, nama, kelas, dan tahun ajaran yang sama akan diperbarui; data lainnya ditambahkan.'],
            ],
        ];
    }

    /** @return array{created: int, updated: int} */
    public function import(string $path): array
    {
        $rows = $this->readRows($path);
        $borrowers = $this->validateRows($rows);

        return DB::transaction(function () use ($borrowers): array {
            $created = 0;
            $updated = 0;

            foreach ($borrowers as $data) {
                $borrower = Borrower::query()->firstOrNew([
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'class_name' => $data['class_name'],
                    'academic_year' => $data['academic_year'],
                ]);
                $wasCreated = ! $borrower->exists;
                $borrower->fill([
                    'contact' => $data['contact'],
                    'is_active' => $data['is_active'],
                ])->save();

                $wasCreated ? $created++ : $updated++;
            }

            return compact('created', 'updated');
        });
    }

    /** @return array<int, array<string, string>> */
    private function readRows(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->invalid('File bukan workbook Excel yang valid. Gunakan template GearTrack berformat .xlsx.');
        }

        try {
            $size = 0;
            if ($zip->numFiles > 1000) {
                $this->invalid('Workbook terlalu kompleks. Gunakan template GearTrack.');
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $size += $entry['size'];
                if ($size > 50 * 1024 * 1024 || str_contains($entry['name'], '..') || str_starts_with($entry['name'], '/')) {
                    $this->invalid('Isi workbook terlalu besar atau tidak valid.');
                }
            }
        } finally {
            $zip->close();
        }

        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
        $rows = [];
        $foundSheet = false;

        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getName() !== 'Data Peminjam') {
                    continue;
                }
                $foundSheet = true;
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number > self::MAX_ROWS + 1) {
                        $this->invalid('Maksimal 1.000 baris data peminjam.');
                    }

                    $values = [];
                    foreach ($row->getCells() as $cell) {
                        if ($cell instanceof FormulaCell) {
                            $this->invalid('Baris '.$number.': rumus tidak didukung. Tempel sebagai nilai.');
                        }
                        $values[] = trim((string) ($cell->getValue() ?? ''));
                    }
                    while (count($values) > count(self::HEADERS) && end($values) === '') {
                        array_pop($values);
                    }

                    if ($number === 1) {
                        if ($values !== self::HEADERS) {
                            $this->invalid('Judul atau urutan kolom tidak sesuai. Unduh template baru dan isi sheet Data Peminjam.');
                        }

                        continue;
                    }
                    if (count(array_filter($values, fn (string $value): bool => $value !== '')) === 0) {
                        continue;
                    }
                    if (count($rows) >= self::MAX_ROWS || count($values) > count(self::HEADERS)) {
                        $this->invalid('Maksimal 1.000 baris data dengan kolom sesuai template.');
                    }

                    $rows[$number] = array_combine(self::HEADERS, array_pad($values, count(self::HEADERS), ''));
                }
                break;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->invalid('Workbook tidak dapat dibaca. Simpan ulang sebagai .xlsx menggunakan template GearTrack.');
        } finally {
            $reader->close();
            unset($sheet, $reader);
            gc_collect_cycles();
        }

        if (! $foundSheet || $rows === []) {
            $this->invalid('Sheet Data Peminjam tidak ditemukan atau belum berisi data.');
        }

        return $rows;
    }

    /** @param array<int, array<string, string>> $rows
     * @return array<int, array{type: string, name: string, class_name: ?string, academic_year: ?string, contact: ?string, is_active: bool}>
     */
    private function validateRows(array $rows): array
    {
        $validated = [];
        $seen = [];
        $errors = [];

        foreach ($rows as $number => $row) {
            $type = $this->normalizeType($row['jenis_peminjam']);
            $name = trim($row['nama']);
            $className = trim($row['kelas']);
            $academicYear = trim($row['tahun_ajaran']);
            $contact = trim($row['kontak']);
            $activeValue = mb_strtolower(trim($row['aktif']));
            $isActive = match ($activeValue) {
                '', 'aktif', 'active', 'ya', 'yes', 'true', '1' => true,
                'nonaktif', 'non-aktif', 'inactive', 'tidak', 'no', 'false', '0' => false,
                default => null,
            };
            $data = [
                'type' => $type,
                'name' => $name,
                'class_name' => $type === 'student' ? $className : null,
                'academic_year' => $type === 'student' ? $academicYear : null,
                'contact' => $contact === '' ? null : $contact,
                'is_active' => $isActive,
            ];
            $validator = Validator::make($data, [
                'type' => ['required', 'string', 'in:staff,student'],
                'name' => ['required', 'string', 'max:150'],
                'class_name' => ['nullable', 'required_if:type,student', 'string', 'max:100'],
                'academic_year' => ['nullable', 'required_if:type,student', 'string', 'max:30'],
                'contact' => ['nullable', 'string', 'max:150'],
                'is_active' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Baris '.$number.': '.$validator->errors()->first();

                continue;
            }

            $identity = mb_strtolower(implode('|', [$data['type'], $data['name'], $data['class_name'] ?? '', $data['academic_year'] ?? '']));
            if (isset($seen[$identity])) {
                $errors[] = 'Baris '.$number.': data peminjam yang sama tercantum lebih dari sekali.';

                continue;
            }
            $seen[$identity] = true;
            $validated[] = $data;
        }

        if ($errors !== []) {
            $this->invalid(implode(' ', array_slice($errors, 0, 10)).(count($errors) > 10 ? ' Perbaiki seluruh data lalu unggah kembali.' : ''));
        }

        return $validated;
    }

    private function normalizeType(string $value): string
    {
        return match (mb_strtolower(trim($value))) {
            'guru', 'tu', 'guru/tu', 'guru / tu', 'staff', 'staf' => 'staff',
            'siswa', 'student' => 'student',
            default => '',
        };
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
