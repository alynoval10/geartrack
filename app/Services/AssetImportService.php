<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;
use ZipArchive;

class AssetImportService
{
    public const HEADERS = ['kode_aset', 'nama_aset', 'kode_kategori', 'nama_merek', 'kode_lokasi', 'model', 'nomor_seri', 'kondisi', 'status', 'tanggal_pengadaan', 'sumber_dana', 'harga_perolehan', 'penanggung_jawab', 'catatan'];

    public const MAX_ROWS = 500;

    /** @return array<string, array> */
    public function template(): array
    {
        return [
            'Aset' => [self::HEADERS],
            'Petunjuk' => [['Kolom', 'Cara mengisi'],
                ['kode_aset', 'Wajib, unik. Gunakan format teks untuk mempertahankan nol di depan.'],
                ['nama_aset', 'Wajib, maksimal 255 karakter.'],
                ['kode_kategori', 'Wajib. Salin kode dari sheet Kategori.'],
                ['nama_merek', 'Opsional. Salin nama dari sheet Merek.'],
                ['kode_lokasi', 'Opsional. Salin kode dari sheet Lokasi.'],
                ['kondisi', 'Baik / Rusak Ringan / Rusak Berat. Kosong berarti Baik.'],
                ['status', 'Tersedia / Digunakan / Dalam Perawatan / Hilang / Nonaktif. Kosong berarti Tersedia. Peminjaman dibuat melalui menu Peminjaman.'],
                ['tanggal_pengadaan', 'Opsional, format YYYY-MM-DD atau sel tanggal Excel.'],
                ['harga_perolehan', 'Opsional, angka rupiah tanpa pemisah ribuan, desimal memakai titik.'],
                ['nomor_seri', 'Opsional, tidak boleh sama dengan nomor seri yang sudah ada.'],
                ['Batas', 'Maksimal 500 aset dan 5 MB. Isi sheet Aset tanpa mengubah judul kolom. Jangan gunakan rumus.'],
                ['Penyimpanan', 'Seluruh baris harus valid. Impor menambah aset baru, tidak mengganti data lama.']],
            'Kategori' => [['Kode', 'Nama'], ...Category::where('is_active', true)->orderBy('name')->get()->map(fn ($item) => [$item->code, $item->name])->all()],
            'Merek' => [['Nama'], ...Brand::where('is_active', true)->orderBy('name')->get()->map(fn ($item) => [$item->name])->all()],
            'Lokasi' => [['Kode', 'Nama'], ...Location::where('is_active', true)->orderBy('name')->get()->map(fn ($item) => [$item->code, $item->name])->all()],
        ];
    }

    /** @return array{rows: array, errors: array, count: int} */
    public function preview(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->invalid('File bukan workbook Excel yang valid.');
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
        $found = false;
        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getName() !== 'Aset') {
                    continue;
                }
                $found = true;
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number > 2001) {
                        $this->invalid('Terlalu banyak baris kosong. Gunakan template baru dan maksimal 500 aset.');
                    }
                    $values = [];
                    foreach ($row->getCells() as $cell) {
                        if ($cell instanceof FormulaCell) {
                            $this->invalid('Baris '.$number.': rumus tidak didukung. Tempel sebagai nilai.');
                        }
                        $value = $cell->getValue();
                        $values[] = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : trim((string) ($value ?? ''));
                    }
                    while (count($values) > count(self::HEADERS) && end($values) === '') {
                        array_pop($values);
                    }
                    if ($number === 1) {
                        if ($values !== self::HEADERS) {
                            $this->invalid('Judul kolom tidak sesuai template. Unduh template lalu isi sheet Aset.');
                        }

                        continue;
                    }
                    if (count(array_filter($values, fn ($value): bool => $value !== '')) === 0) {
                        continue;
                    }
                    if (count($rows) >= self::MAX_ROWS || count($values) > count(self::HEADERS)) {
                        $this->invalid('Maksimal 500 aset dengan kolom sesuai template.');
                    }
                    $values = array_pad($values, count(self::HEADERS), '');
                    foreach ($values as $value) {
                        if (mb_strlen($value) > 2000) {
                            $this->invalid('Baris '.$number.': isi sel terlalu panjang (maksimal 2.000 karakter).');
                        }
                    }
                    $rows[$number] = array_combine(self::HEADERS, $values);
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
            // OpenSpout row callbacks retain XML streams after an early exit on Windows.
            gc_collect_cycles();
        }
        if (! $found || $rows === []) {
            $this->invalid('Sheet Aset tidak ditemukan atau belum berisi data.');
        }
        $checked = $this->validateRows($rows);

        return ['rows' => $rows, 'errors' => $checked['errors'], 'count' => count($rows)];
    }

    /** @param array<int, array<string, string>> $rows
     * @return array{assets: array, errors: array}
     */
    public function validateRows(array $rows): array
    {
        $categories = Category::where('is_active', true)->get()->groupBy(fn ($item) => mb_strtolower($item->code));
        $brands = Brand::where('is_active', true)->get()->groupBy(fn ($item) => mb_strtolower($item->name));
        $locations = Location::where('is_active', true)->get()->groupBy(fn ($item) => mb_strtolower($item->code));
        $existingCodes = Asset::pluck('asset_code')->mapWithKeys(fn ($code) => [mb_strtolower(trim($code)) => true])->all();
        $existingSerials = Asset::whereNotNull('serial_number')->pluck('serial_number')->mapWithKeys(fn ($serial) => [mb_strtolower(trim($serial)) => true])->all();
        $seenCodes = $seenSerials = $assets = $errors = [];
        foreach ($rows as $number => $row) {
            $messages = [];
            $category = $categories->get(mb_strtolower($row['kode_kategori']));
            $brand = $brands->get(mb_strtolower($row['nama_merek']));
            $location = $locations->get(mb_strtolower($row['kode_lokasi']));
            if ($category?->count() !== 1) {
                $messages[] = 'Kode kategori tidak ditemukan, tidak aktif, atau tidak unik.';
            }
            if ($row['nama_merek'] !== '' && $brand?->count() !== 1) {
                $messages[] = 'Nama merek tidak ditemukan, tidak aktif, atau tidak unik.';
            }
            if ($row['kode_lokasi'] !== '' && $location?->count() !== 1) {
                $messages[] = 'Kode lokasi tidak ditemukan, tidak aktif, atau tidak unik.';
            }
            $code = mb_strtolower($row['kode_aset']);
            $serial = mb_strtolower($row['nomor_seri']);
            if (isset($existingCodes[$code]) || isset($seenCodes[$code])) {
                $messages[] = 'Kode aset sudah ada atau berulang dalam file.';
            }
            if ($serial !== '' && (isset($existingSerials[$serial]) || isset($seenSerials[$serial]))) {
                $messages[] = 'Nomor seri sudah ada atau berulang dalam file.';
            }
            $seenCodes[$code] = true;
            if ($serial !== '') {
                $seenSerials[$serial] = true;
            }
            $condition = mb_strtolower($row['kondisi']);
            $status = mb_strtolower($row['status']);
            $data = [
                'asset_code' => $row['kode_aset'], 'name' => $row['nama_aset'], 'category_id' => $category?->first()?->id,
                'brand_id' => $brand?->first()?->id, 'location_id' => $location?->first()?->id,
                'model' => $row['model'] ?: null, 'serial_number' => $row['nomor_seri'] === '' ? null : $row['nomor_seri'],
                'condition' => ['' => 'good', 'baik' => 'good', 'rusak ringan' => 'minor_damage', 'rusak berat' => 'major_damage'][$condition] ?? $condition,
                'status' => ['' => 'available', 'tersedia' => 'available', 'digunakan' => 'in_use', 'dalam perawatan' => 'maintenance', 'hilang' => 'lost', 'nonaktif' => 'retired'][$status] ?? $status,
                'acquisition_date' => $row['tanggal_pengadaan'] ?: null, 'funding_source' => $row['sumber_dana'] ?: null,
                'purchase_price' => $row['harga_perolehan'] === '' ? null : $row['harga_perolehan'],
                'custodian_name' => $row['penanggung_jawab'] ?: null, 'notes' => $row['catatan'] ?: null,
            ];
            $validator = Validator::make($data, [
                'asset_code' => ['required', 'string', 'max:255', 'regex:/^[\pL\pN][\pL\pN._\/-]*$/u'],
                'name' => ['required', 'string', 'max:255'], 'model' => ['nullable', 'string', 'max:255'],
                'serial_number' => ['nullable', 'string', 'max:255'], 'funding_source' => ['nullable', 'string', 'max:255'],
                'custodian_name' => ['nullable', 'string', 'max:150'], 'notes' => ['nullable', 'string', 'max:2000'],
                'condition' => ['required', Rule::in(['good', 'minor_damage', 'major_damage'])],
                'status' => ['required', Rule::in(['available', 'in_use', 'maintenance', 'lost', 'retired'])],
                'acquisition_date' => ['nullable', 'date_format:Y-m-d'],
                'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            ]);
            $messages = [...$messages, ...$validator->errors()->all()];
            if ($messages !== []) {
                $errors[$number] = $messages;
            }
            $assets[] = $data;
        }

        return ['assets' => $assets, 'errors' => $errors];
    }

    /** @param array<int, array<string, string>> $rows */
    public function import(array $rows): int
    {
        if ($rows === [] || count($rows) > self::MAX_ROWS) {
            $this->invalid('Jumlah baris impor tidak valid.');
        }

        return DB::transaction(function () use ($rows): int {
            $checked = $this->validateRows($rows);
            if ($checked['errors'] !== []) {
                $this->invalid('Data berubah atau ada baris tidak valid. Unggah ulang untuk memeriksa: '.collect($checked['errors'])->flatten()->first());
            }
            foreach ($checked['assets'] as $asset) {
                Asset::create($asset);
            }

            return count($checked['assets']);
        });
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
