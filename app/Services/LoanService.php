<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetSet;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\MaintenanceReport;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function __construct(private AssetSelection $selection, private MaintenanceService $maintenance) {}

    /**
     * Buat peminjaman dari satu mode pilihan dan simpan nama penanggung jawab sebagai snapshot.
     *
     * @param  array<string, mixed>  $data
     */
    public function borrow(array $data, User $user): Loan
    {
        Gate::forUser($user)->authorize('create', Loan::class);

        $data = Validator::make($data, [
            'borrower_type' => ['required', Rule::in(array_keys(Borrower::TYPES))],
            'borrower_id' => ['required', 'integer', 'exists:borrowers,id'],
            'borrower_level' => ['nullable', 'string', 'required_if:borrower_type,student', Rule::in(array_keys(Borrower::LEVELS))],
            'borrower_class' => ['nullable', 'string', 'required_if:borrower_type,student', 'max:100'],
            'responsible_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'selection_type' => ['required', Rule::in(['asset', 'package'])],
            'purpose' => ['required', 'string', 'max:5000'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'asset_set_id' => ['required_if:selection_type,package', 'nullable', 'integer', 'exists:asset_sets,id'],
            'asset_ids' => ['required_if:selection_type,asset', 'nullable', 'array', 'min:1', 'max:100'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
        ])->validate();

        if (! $user->isAdmin() && (int) $data['responsible_user_id'] !== $user->id) {
            throw ValidationException::withMessages(['responsible_user_id' => 'Guru hanya dapat mencatat peminjaman dengan dirinya sendiri sebagai penanggung jawab.']);
        }

        return DB::transaction(function () use ($data, $user): Loan {
            $borrower = Borrower::query()
                ->whereKey($data['borrower_id'])
                ->where('type', $data['borrower_type'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $borrower) {
                throw ValidationException::withMessages(['borrower_id' => 'Pilih data peminjam yang masih aktif.']);
            }

            if ($borrower->type === 'student') {
                if (blank($borrower->nis)) {
                    throw ValidationException::withMessages(['borrower_id' => 'Lengkapi NIS siswa di menu Data Peminjam sebelum membuat transaksi.']);
                }

                $currentAcademicYear = SchoolSetting::query()->value('academic_year');

                if (blank($currentAcademicYear)) {
                    throw ValidationException::withMessages(['borrower_id' => 'Atur tahun ajaran aktif di Pengaturan Sekolah terlebih dahulu.']);
                }

                if ($borrower->academic_year !== $currentAcademicYear
                    || $borrower->class_level !== $data['borrower_level']
                    || $borrower->class_name !== $data['borrower_class']) {
                    throw ValidationException::withMessages(['borrower_id' => 'Siswa tidak sesuai dengan kelas atau tahun ajaran aktif. Pilih ulang data peminjam.']);
                }
            }

            $responsibleUser = User::query()
                ->whereKey($data['responsible_user_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($data['selection_type'] === 'asset') {
                $data['asset_set_id'] = null;
            } else {
                $data['asset_ids'] = [];
            }

            $assets = $this->selection->lock($data);
            foreach ($assets as $asset) {
                if (! in_array($asset->status, ['available', 'in_use'], true) || $asset->condition !== 'good'
                    || $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                    || $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['asset_ids' => $asset->asset_code.' tidak siap dipinjam atau masih memiliki transaksi aktif.']);
                }
            }
            $loan = Loan::create([
                ...collect($data)->except(['asset_ids', 'selection_type', 'borrower_class', 'borrower_level'])->all(),
                'borrower_id' => $borrower->id,
                'borrower_type' => $borrower->type,
                'borrower_name' => $borrower->name,
                'borrower_nis' => $borrower->type === 'student' ? $borrower->nis : null,
                'borrower_contact' => $borrower->type === 'staff' ? $borrower->contact : null,
                'borrower_class' => $borrower->type === 'student' ? trim($borrower->class_level.' '.$borrower->class_name) : null,
                'borrower_academic_year' => $borrower->type === 'student' ? $borrower->academic_year : null,
                'responsible_name' => $responsibleUser->name,
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
        Gate::forUser($user)->authorize('receive', $loan);

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
