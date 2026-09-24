<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MaintenanceScheduleService
{
    public function __construct(private MaintenanceService $maintenance) {}

    public function save(array $data, User $user, ?MaintenanceSchedule $schedule = null): MaintenanceSchedule
    {
        $data = Validator::make($data, [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:150'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'technician' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($data, $user, $schedule): MaintenanceSchedule {
            $asset = Asset::query()->lockForUpdate()->findOrFail($data['asset_id']);
            if ($schedule) {
                $schedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
                if ($schedule->asset_id !== $asset->id || $schedule->reports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['title' => 'Aset jadwal tidak boleh diganti dan jadwal dengan laporan aktif tidak dapat diedit.']);
                }
                $schedule->update($data);

                return $schedule;
            }

            return MaintenanceSchedule::create([
                ...$data, 'asset_code' => $asset->asset_code, 'asset_name' => $asset->name, 'created_by' => $user->id,
            ]);
        });
    }

    public function createReport(MaintenanceSchedule $schedule, User $user): MaintenanceReport
    {
        return DB::transaction(function () use ($schedule, $user): MaintenanceReport {
            $asset = $schedule->asset_id ? Asset::query()->lockForUpdate()->find($schedule->asset_id) : null;
            $schedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if (! $schedule->is_active || ! $asset || in_array($asset->status, ['borrowed', 'lost', 'retired'], true)) {
                throw ValidationException::withMessages(['schedule' => 'Jadwal harus aktif dan aset harus tersedia untuk perawatan.']);
            }
            $report = $this->maintenance->report($asset, [
                'type' => 'maintenance', 'title' => $schedule->title,
                'description' => $schedule->notes ?: 'Perawatan terjadwal '.$schedule->due_date->format('d/m/Y'),
                'reported_condition' => $asset->condition,
            ], $user);
            $report->update([
                'maintenance_schedule_id' => $schedule->id, 'schedule_due_date' => $schedule->due_date,
                'technician' => $schedule->technician,
            ]);

            return $report;
        });
    }
}
