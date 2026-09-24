<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\MaintenanceReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function __construct(private AssetSelection $selection, private MaintenanceService $maintenance) {}

    public function borrow(array $data, User $user): Loan
    {
        $data = Validator::make($data, [
            'borrower_name' => ['required', 'string', 'max:150'],
            'borrower_contact' => ['nullable', 'string', 'max:150'],
            'responsible_name' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:5000'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'asset_set_id' => ['nullable', 'integer', 'exists:asset_sets,id'],
            'asset_ids' => ['nullable', 'array', 'max:100'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
        ])->validate();

        return DB::transaction(function () use ($data, $user): Loan {
            $assets = $this->selection->lock($data);
            foreach ($assets as $asset) {
                if (! in_array($asset->status, ['available', 'in_use'], true) || $asset->condition !== 'good'
                    || $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                    || $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['asset_ids' => $asset->asset_code.' tidak siap dipinjam atau masih memiliki transaksi aktif.']);
                }
            }
            $loan = Loan::create([
                ...collect($data)->except('asset_ids')->all(),
                'code' => 'PJM-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'package_name' => empty($data['asset_set_id']) ? null : AssetSet::findOrFail($data['asset_set_id'])->name,
                'borrowed_at' => now(), 'status' => 'open', 'created_by' => $user->id,
            ]);
            foreach ($assets as $asset) {
                $loan->items()->create([
                    'asset_id' => $asset->id, 'active_asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code, 'asset_name' => $asset->name,
                    'condition_before' => $asset->condition, 'previous_status' => $asset->status,
                ]);
                $asset->update(['status' => 'borrowed']);
                $asset->histories()->create([
                    'user_id' => $user->id, 'action' => 'loan',
                    'description' => "Dipinjam oleh {$loan->borrower_name} ({$loan->code}), batas kembali ".$loan->due_date->format('d/m/Y').'.',
                ]);
            }

            return $loan;
        });
    }

    public function receive(Loan $loan, LoanItem $item, array $data, User $user): void
    {
        $data = Validator::make($data, [
            'condition' => ['required', Rule::in(array_keys(MaintenanceReport::CONDITIONS))],
            'notes' => [Rule::requiredIf(($data['condition'] ?? 'good') !== 'good'), 'nullable', 'string', 'max:5000'],
        ])->validate();

        DB::transaction(function () use ($loan, $item, $data, $user): void {
            $asset = Asset::query()->lockForUpdate()->findOrFail($item->asset_id);
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            $item = $loan->items()->lockForUpdate()->findOrFail($item->id);
            if ($item->returned_at || $loan->status !== 'open') {
                throw ValidationException::withMessages(['condition' => 'Perangkat sudah dikembalikan.']);
            }
            $item->update([
                'active_asset_id' => null, 'condition_after' => $data['condition'],
                'return_notes' => $data['notes'] ?? null, 'returned_at' => now(), 'received_by' => $user->id,
            ]);
            $openReport = $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->first();
            $needsCare = $data['condition'] !== 'good' || $openReport;
            $asset->update([
                'condition' => $data['condition'],
                'status' => $needsCare ? 'maintenance' : $item->previous_status,
            ]);
            if ($data['condition'] !== 'good' && ! $openReport) {
                $this->maintenance->report($asset, [
                    'type' => 'damage', 'title' => 'Kerusakan saat pengembalian '.$loan->code,
                    'description' => $data['notes'], 'reported_condition' => $data['condition'],
                ], $user);
            } elseif ($openReport) {
                $this->maintenance->update($openReport, 'note', [
                    'notes' => 'Perangkat telah dikembalikan pada '.$loan->code.'. '.($data['notes'] ?? ''),
                ], $user);
            }
            $asset->histories()->create([
                'user_id' => $user->id, 'action' => 'loan_return',
                'description' => "Dikembalikan pada {$loan->code}. Kondisi: ".MaintenanceReport::CONDITIONS[$data['condition']].'.',
            ]);
            if (! $loan->items()->whereNull('returned_at')->exists()) {
                $loan->update(['status' => 'returned', 'returned_at' => now()]);
            }
        });
    }
}
