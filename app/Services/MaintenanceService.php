<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceEntry;
use App\Models\MaintenanceReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaintenanceService
{
    public function report(Asset $asset, array $data, User $user): MaintenanceReport
    {
        $data = Validator::make($data, [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(MaintenanceReport::TYPES))],
            'description' => ['required', 'string', 'max:5000'],
            'reported_condition' => ['required', Rule::in(array_keys(MaintenanceReport::CONDITIONS))],
        ])->validate();

        if ($data['type'] === 'damage' && $data['reported_condition'] === 'good') {
            throw ValidationException::withMessages(['reported_condition' => 'Pilih tingkat kerusakan untuk laporan kerusakan.']);
        }

        return DB::transaction(function () use ($asset, $data, $user): MaintenanceReport {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                throw ValidationException::withMessages(['title' => 'Aset ini masih memiliki laporan aktif. Tambahkan catatan pada laporan tersebut.']);
            }

            $report = $asset->maintenanceReports()->create([
                ...$data,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->name,
                'status' => 'open',
                'reported_by' => $user->id,
            ]);
            $asset->update(['condition' => $data['reported_condition']]);
            $this->entry($report, $user, 'reported', $data['description']);

            return $report;
        });
    }

    public function update(MaintenanceReport $report, string $action, array $data, User $user): MaintenanceReport
    {
        $data = Validator::make($data, [
            'notes' => ['required', 'string', 'max:5000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'technician' => [Rule::requiredIf($action === 'started'), 'nullable', 'string', 'max:150'],
            'condition' => [Rule::requiredIf($action === 'resolved'), 'nullable', Rule::in(array_keys(MaintenanceReport::CONDITIONS))],
            'asset_status' => [Rule::requiredIf($action === 'resolved'), 'nullable', Rule::in(['available', 'in_use', 'retired'])],
        ])->validate();

        return DB::transaction(function () use ($report, $action, $data, $user): MaintenanceReport {
            $asset = $report->asset_id ? Asset::query()->lockForUpdate()->find($report->asset_id) : null;
            $report = MaintenanceReport::query()->lockForUpdate()->findOrFail($report->id);
            if (! $report->isOpen()) {
                throw ValidationException::withMessages(['notes' => 'Laporan sudah ditutup. Buat laporan baru untuk kejadian berikutnya.']);
            }

            if (! in_array($action, ['started', 'note', 'resolved', 'cancelled'], true)) {
                throw ValidationException::withMessages(['notes' => 'Tindakan tidak valid.']);
            }

            if (in_array($action, ['started', 'resolved'], true) && (! $asset || in_array($asset->status, ['borrowed', 'lost', 'retired'], true))) {
                throw ValidationException::withMessages(['notes' => 'Aset harus tersedia atau digunakan sebelum dapat ditangani/diselesaikan. Periksa status inventarisnya.']);
            }

            if ($action === 'started') {
                if ($report->status !== 'open') {
                    throw ValidationException::withMessages(['notes' => 'Penanganan sudah dimulai. Gunakan Tambah Catatan.']);
                }
                $report->fill([
                    'status' => 'in_progress',
                    'technician' => $data['technician'],
                    'previous_asset_status' => $asset->status,
                ]);
                $asset->update(['status' => 'maintenance']);
            } elseif ($action === 'resolved') {
                if ($report->status !== 'in_progress') {
                    throw ValidationException::withMessages(['notes' => 'Mulai penanganan sebelum menyelesaikan laporan.']);
                }
                if ($data['condition'] === 'major_damage' && $data['asset_status'] !== 'retired') {
                    throw ValidationException::withMessages(['asset_status' => 'Aset rusak berat harus dinonaktifkan, atau lanjutkan penanganan.']);
                }
                $asset->update(['condition' => $data['condition'], 'status' => $data['asset_status']]);
                $report->fill(['status' => 'resolved', 'closed_at' => now()]);
            } elseif ($action === 'cancelled') {
                if ($asset?->status === 'maintenance' && $report->status === 'in_progress') {
                    $asset->update(['status' => in_array($report->previous_asset_status, ['available', 'in_use', 'maintenance'], true)
                        ? $report->previous_asset_status : 'available']);
                }
                $report->fill(['status' => 'cancelled', 'closed_at' => now()]);
            }

            $report->save();
            $this->entry($report, $user, $action, $data['notes'], $data['cost'] ?? 0);

            return $report;
        });
    }

    private function entry(MaintenanceReport $report, User $user, string $action, string $notes, int|float|string $cost = 0): void
    {
        $report->entries()->create([
            'user_id' => $user->id,
            'action' => $action,
            'notes' => $notes,
            'cost' => $cost,
        ]);
        $report->asset?->histories()->create([
            'user_id' => $user->id,
            'action' => 'maintenance',
            'description' => MaintenanceEntry::ACTIONS[$action].": {$report->title}.",
        ]);
    }
}
