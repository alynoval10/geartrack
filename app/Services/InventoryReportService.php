<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;
use App\Models\LoanItem;
use App\Models\Location;
use App\Models\MaintenanceReport;
use App\Models\StockTakeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryReportService
{
    public const TYPES = ['assets' => 'Inventaris Aset', 'overdue' => 'Peminjaman Terlambat', 'stock_take' => 'Hasil Stock Opname', 'maintenance' => 'Biaya Perawatan'];

    public const STATUSES = ['available' => 'Tersedia', 'in_use' => 'Digunakan', 'borrowed' => 'Dipinjam', 'maintenance' => 'Dalam Perawatan', 'lost' => 'Hilang', 'retired' => 'Nonaktif'];

    /** @param array<string, mixed> $filters
     * @return array{title: string, headers: list<string>, rows: array, summary: string, period: string}
     */
    public function generate(array $filters): array
    {
        $type = $filters['type'];
        $query = match ($type) {
            'assets' => Asset::with(['category', 'location', 'brand', 'assetSet']),
            'overdue' => LoanItem::with('loan')->whereNull('returned_at')->whereHas('loan', fn (Builder $query) => $query->where('status', 'open')->whereDate('due_date', '<', today())),
            'stock_take' => StockTakeItem::with(['stockTake', 'checker'])->where('stock_take_id', $filters['stock_take_id']),
            'maintenance' => MaintenanceReport::with('asset.location')->withSum('entries', 'cost'),
        };
        if ($type === 'assets') {
            foreach (['location_id', 'category_id', 'condition', 'status'] as $field) {
                if (filled($filters[$field] ?? null)) {
                    $query->where($field, $filters[$field]);
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
            'assets' => ['Kode Aset', 'Nama', 'Kategori', 'Merek', 'Model', 'Nomor Seri', 'Lokasi', 'Penanggung Jawab', 'Paket', 'Kondisi', 'Status', 'Tanggal Pengadaan', 'Harga Perolehan (Rp)'],
            'overdue' => ['Kode Pinjaman', 'Peminjam', 'Penanggung Jawab', 'Kode Aset', 'Nama Aset', 'Tanggal Pinjam', 'Jatuh Tempo', 'Terlambat (hari)'],
            'stock_take' => ['Stock Opname', 'Kode Aset', 'Nama Aset', 'Lokasi Awal', 'Lokasi Ditemukan', 'Hasil', 'Diperiksa Pada', 'Petugas', 'Catatan'],
            'maintenance' => ['Kode Aset', 'Nama Aset', 'Laporan', 'Jenis', 'Status', 'Teknisi', 'Tanggal Laporan', 'Selesai Pada', 'Biaya (Rp)'],
        };
        $rows = $records->map(fn ($record): array => match ($type) {
            'assets' => [$record->asset_code, $record->name, $record->category?->name, $record->brand?->name, $record->model, $record->serial_number, $record->location?->name, $record->custodian_name, $record->assetSet?->name, MaintenanceReport::CONDITIONS[$record->condition] ?? $record->condition, self::STATUSES[$record->status] ?? $record->status, $record->acquisition_date?->format('Y-m-d'), $record->purchase_price === null ? null : (float) $record->purchase_price],
            'overdue' => [$record->loan->code, $record->loan->borrower_name, $record->loan->responsible_name, $record->asset_code, $record->asset_name, $record->loan->borrowed_at->format('Y-m-d'), $record->loan->due_date->format('Y-m-d'), (int) $record->loan->due_date->diffInDays(today())],
            'stock_take' => [$record->stockTake->name, $record->asset_code, $record->asset_name, $record->expected_location_name, $record->observed_location_name, StockTakeItem::RESULTS[$record->result], $record->checked_at?->format('Y-m-d H:i'), $record->checker?->name, $record->notes],
            'maintenance' => [$record->asset_code, $record->asset_name, $record->title, MaintenanceReport::TYPES[$record->type], MaintenanceReport::STATUSES[$record->status], $record->technician, $record->created_at->format('Y-m-d'), $record->closed_at?->format('Y-m-d'), (float) ($record->entries_sum_cost ?? 0)],
        })->all();
        $summary = count($rows).' baris';
        if (in_array($type, ['assets', 'maintenance'], true)) {
            $sum = $records->sum($type === 'assets' ? 'purchase_price' : 'entries_sum_cost');
            $summary .= ' · Total '.($type === 'assets' ? 'nilai perolehan' : 'biaya tercatat').': Rp '.number_format($sum, 2, ',', '.');
        }
        $filterLabels = [];
        if ($type === 'assets') {
            foreach (['location_id' => Location::class, 'category_id' => Category::class] as $key => $model) {
                if (filled($filters[$key] ?? null)) {
                    $filterLabels[] = $model::find($filters[$key])?->name;
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
}
