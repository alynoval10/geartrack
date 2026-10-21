<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Borrower;
use App\Models\Category;
use App\Models\LoanItem;
use App\Models\Location;
use App\Models\MaintenanceReport;
use App\Models\StockTakeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryReportService
{
    public const TYPES = ['assets' => 'Inventaris Aset', 'category_summary' => 'Rekap Aset per Kategori', 'loans' => 'Data Peminjaman', 'overdue' => 'Peminjaman Terlambat', 'stock_take' => 'Hasil Stock Opname', 'maintenance' => 'Biaya Perawatan'];

    public const STATUSES = ['available' => 'Tersedia', 'in_use' => 'Digunakan', 'borrowed' => 'Dipinjam', 'maintenance' => 'Dalam Perawatan', 'lost' => 'Hilang', 'retired' => 'Nonaktif'];

    /** @param array<string, mixed> $filters
     * @return array{title: string, headers: list<string>, rows: array, summary: string, period: string}
     */
    public function generate(array $filters): array
    {
        $type = $filters['type'];

        if ($type === 'category_summary') {
            return $this->generateCategorySummary($filters);
        }

        if ($type === 'loans') {
            return $this->generateLoans($filters);
        }

        $query = match ($type) {
            'assets' => Asset::with(['category', 'location.parent', 'brand', 'assetSet']),
            'overdue' => LoanItem::with('loan')->whereNull('returned_at')->whereHas('loan', fn (Builder $query) => $query->where('status', 'open')->whereDate('due_date', '<', today())),
            'stock_take' => StockTakeItem::with(['stockTake', 'checker'])->where('stock_take_id', $filters['stock_take_id']),
            'maintenance' => MaintenanceReport::with('asset.location')->withSum('entries', 'cost'),
        };
        if ($type === 'assets') {
            foreach (['location_id', 'category_id', 'condition', 'status'] as $field) {
                if (filled($filters[$field] ?? null)) {
                    if ($field === 'location_id') {
                        $location = Location::findOrFail($filters[$field]);
                        $locationIds = filter_var($filters['include_children'] ?? false, FILTER_VALIDATE_BOOLEAN)
                            ? $location->hierarchyIds()
                            : [$location->id];
                        $query->whereIn('location_id', $locationIds);
                    } else {
                        $query->where($field, $filters[$field]);
                    }
                }
            }
        }
        if ($type === 'stock_take' && filled($filters['result'] ?? null)) {
            $query->where('result', $filters['result']);
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (! filled($filters[$key] ?? null)) {
                continue;
            }
            if ($type === 'overdue') {
                $query->whereHas('loan', fn (Builder $loan) => $loan->whereDate('due_date', $operator, $filters[$key]));
            } else {
                $column = match ($type) {
                    'assets' => 'acquisition_date', 'stock_take' => 'checked_at', default => 'created_at'
                };
                $query->whereDate($column, $operator, $filters[$key]);
            }
        }
        $records = $query->orderBy('id')->limit(5001)->get();
        if ($records->count() > 5000) {
            throw ValidationException::withMessages(['report' => 'Hasil melebihi 5.000 baris. Persempit filter laporan.']);
        }
        $headers = match ($type) {
            'assets' => ['No.', 'Kode Aset', 'Nama', 'Kategori', 'Merek', 'Model', 'Nomor Seri', 'Lokasi', 'Penanggung Jawab', 'Paket', 'Kondisi', 'Status', 'Tanggal Pengadaan', 'Harga Perolehan (Rp)'],
            'overdue' => ['No.', 'Kode Pinjaman', 'Peminjam', 'Jenis Peminjam', 'Kelas', 'Tahun Ajaran', 'Penanggung Jawab', 'Kode Aset', 'Nama Aset', 'Tanggal Pinjam', 'Jatuh Tempo', 'Terlambat (hari)'],
            'stock_take' => ['No.', 'Stock Opname', 'Kode Aset', 'Nama Aset', 'Lokasi Awal', 'Lokasi Ditemukan', 'Hasil', 'Diperiksa Pada', 'Petugas', 'Catatan'],
            'maintenance' => ['No.', 'Kode Aset', 'Nama Aset', 'Laporan', 'Jenis', 'Status', 'Teknisi', 'Tanggal Laporan', 'Selesai Pada', 'Biaya (Rp)'],
        };
        $rows = $records->values()->map(fn ($record, int $index): array => array_merge([$index + 1], match ($type) {
            'assets' => [$record->asset_code, $record->name, $record->category?->name, $record->brand?->name, $record->model, $record->serial_number, $record->location?->fullName(), $record->custodian_name, $record->assetSet?->name, MaintenanceReport::CONDITIONS[$record->condition] ?? $record->condition, self::STATUSES[$record->status] ?? $record->status, $record->acquisition_date?->format('Y-m-d'), $record->purchase_price === null ? null : (float) $record->purchase_price],
            'overdue' => [$record->loan->code, $record->loan->borrower_name, Borrower::TYPES[$record->loan->borrower_type] ?? '', $record->loan->borrower_class, $record->loan->borrower_academic_year, $record->loan->responsible_name, $record->asset_code, $record->asset_name, $record->loan->borrowed_at->format('Y-m-d'), $record->loan->due_date->format('Y-m-d'), (int) $record->loan->due_date->diffInDays(today())],
            'stock_take' => [$record->stockTake->name, $record->asset_code, $record->asset_name, $record->expected_location_name, $record->observed_location_name, StockTakeItem::RESULTS[$record->result], $record->checked_at?->format('Y-m-d H:i'), $record->checker?->name, $record->notes],
            'maintenance' => [$record->asset_code, $record->asset_name, $record->title, MaintenanceReport::TYPES[$record->type], MaintenanceReport::STATUSES[$record->status], $record->technician, $record->created_at->format('Y-m-d'), $record->closed_at?->format('Y-m-d'), (float) ($record->entries_sum_cost ?? 0)],
        }))->all();
        $summary = count($rows).' baris';
        if ($type === 'assets' && filter_var($filters['show_acquisition_total'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $sum = $records->sum('purchase_price');
            $summary .= ' · Total nilai perolehan: Rp '.number_format($sum, 2, ',', '.');
        } elseif ($type === 'maintenance') {
            $sum = $records->sum('entries_sum_cost');
            $summary .= ' · Total biaya tercatat: Rp '.number_format($sum, 2, ',', '.');
        }
        $filterLabels = [];
        if ($type === 'assets') {
            foreach (['location_id' => Location::class, 'category_id' => Category::class] as $key => $model) {
                if (filled($filters[$key] ?? null)) {
                    $filterLabels[] = $key === 'location_id'
                        ? Location::with('parent')->find($filters[$key])?->fullName()
                        : $model::find($filters[$key])?->name;
                }
            }
            if (filled($filters['condition'] ?? null)) {
                $filterLabels[] = MaintenanceReport::CONDITIONS[$filters['condition']];
            }
            if (filled($filters['status'] ?? null)) {
                $filterLabels[] = self::STATUSES[$filters['status']];
            }
        }
        if ($filterLabels !== []) {
            $summary .= ' · Filter: '.implode(' / ', $filterLabels);
        }

        return ['title' => self::TYPES[$type], 'headers' => $headers, 'rows' => $rows, 'summary' => $summary,
            'period' => ($filters['date_from'] ?? null ?: 'Awal').' s.d. '.($filters['date_to'] ?? null ?: 'Sekarang')];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, headers: list<string>, rows: array, summary: string, period: string}
     */
    private function generateLoans(array $filters): array
    {
        $query = LoanItem::query()->with('loan')->whereHas('loan', function (Builder $loanQuery) use ($filters): void {
            if (filled($filters['loan_month'] ?? null)) {
                $year = (int) substr($filters['loan_month'], 0, 4);
                $month = (int) substr($filters['loan_month'], 5, 2);
                $loanQuery->whereYear('borrowed_at', $year)->whereMonth('borrowed_at', $month);
            }
        });
        $records = $query->orderBy('loan_id')->orderBy('id')->limit(5001)->get();

        if ($records->count() > 5000) {
            throw ValidationException::withMessages(['report' => 'Hasil melebihi 5.000 baris. Persempit filter laporan.']);
        }

        $rows = $records->values()->map(fn (LoanItem $item, int $index): array => [
            $index + 1,
            $item->loan->code,
            $item->loan->borrower_name,
            Borrower::TYPES[$item->loan->borrower_type] ?? '',
            $item->loan->borrower_class,
            $item->loan->borrower_academic_year,
            $item->loan->borrower_contact,
            $item->loan->responsible_name,
            $item->loan->purpose,
            $item->asset_code,
            $item->asset_name,
            $item->loan->borrowed_at->format('Y-m-d'),
            $item->loan->due_date->format('Y-m-d'),
            $item->returned_at?->format('Y-m-d'),
            $item->returned_at ? 'Dikembalikan' : 'Dipinjam',
        ])->all();

        return [
            'title' => self::TYPES['loans'],
            'headers' => ['No.', 'Nomor Peminjaman', 'Peminjam', 'Jenis Peminjam', 'Kelas', 'Tahun Ajaran', 'Kontak', 'Penanggung Jawab', 'Keperluan', 'Kode Aset', 'Nama Aset', 'Tanggal Pinjam', 'Batas Kembali', 'Tanggal Kembali', 'Status Perangkat'],
            'rows' => $rows,
            'summary' => $records->pluck('loan_id')->unique()->count().' transaksi · '.count($rows).' perangkat',
            'period' => filled($filters['loan_month'] ?? null)
                ? substr($filters['loan_month'], 5, 2).'/'.substr($filters['loan_month'], 0, 4)
                : 'Semua data',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, headers: list<string>, rows: array, summary: string, period: string}
     */
    private function generateCategorySummary(array $filters): array
    {
        $query = Asset::query()
            ->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
            ->selectRaw('assets.category_id, COALESCE(categories.name, ?) AS category_name, COUNT(assets.id) AS asset_count', ['Tanpa Kategori'])
            ->groupBy('assets.category_id', 'categories.name')
            ->orderBy('categories.name')
            ->orderBy('assets.category_id');

        if (filled($filters['location_id'] ?? null)) {
            $location = Location::findOrFail($filters['location_id']);
            $locationIds = filter_var($filters['include_children'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ? $location->hierarchyIds()
                : [$location->id];

            $query->whereIn('assets.location_id', $locationIds);
        }

        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (filled($filters[$key] ?? null)) {
                $query->whereDate('assets.acquisition_date', $operator, $filters[$key]);
            }
        }

        $categoryCounts = $query->limit(5001)->get();

        if ($categoryCounts->count() > 5000) {
            throw ValidationException::withMessages(['report' => 'Hasil melebihi 5.000 baris. Persempit filter laporan.']);
        }

        $rows = $categoryCounts->values()->map(fn (Asset $category, int $index): array => [
            $index + 1,
            $category->category_name,
            (int) $category->asset_count,
        ])->all();
        $totalAssets = $categoryCounts->sum('asset_count');
        $summary = count($rows).' kategori · Total aset: '.number_format($totalAssets, 0, ',', '.');

        if (filled($filters['location_id'] ?? null)) {
            $locationName = Location::with('parent')->find($filters['location_id'])?->fullName();
            $summary .= ' · Lokasi: '.$locationName;

            if (filter_var($filters['include_children'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $summary .= ' termasuk sub-lokasi';
            }
        }

        return [
            'title' => self::TYPES['category_summary'],
            'headers' => ['No.', 'Kategori', 'Jumlah Aset'],
            'rows' => $rows,
            'summary' => $summary,
            'period' => ($filters['date_from'] ?? null ?: 'Awal').' s.d. '.($filters['date_to'] ?? null ?: 'Sekarang'),
        ];
    }
}
