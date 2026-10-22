<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Notifications\OperationalRemindersNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('geartrack:send-reminders')]
#[Description('Kirim email ringkasan peminjaman terlambat, jadwal perawatan, dan garansi yang akan berakhir.')]
class SendOperationalReminders extends Command
{
    public function handle(): int
    {
        $today = today();
        $maintenanceUntil = $today->copy()->addDays(max(0, (int) config('operational_reminders.maintenance_days', 7)));
        $warrantyUntil = $today->copy()->addDays(max(0, (int) config('operational_reminders.warranty_days', 30)));

        $overdueLoans = Loan::query()
            ->with('items.asset')
            ->where('status', 'open')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        $maintenanceSchedules = MaintenanceSchedule::query()
            ->with('asset')
            ->where('is_active', true)
            ->whereHas('asset')
            ->whereDate('due_date', '<=', $maintenanceUntil)
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        $expiringWarranties = Asset::query()
            ->whereNotNull('warranty_end_date')
            ->whereBetween('warranty_end_date', [$today->toDateString(), $warrantyUntil->toDateString()])
            ->whereNotIn('status', ['retired', 'lost'])
            ->orderBy('warranty_end_date')
            ->limit(50)
            ->get();

        if ($overdueLoans->isEmpty() && $maintenanceSchedules->isEmpty() && $expiringWarranties->isEmpty()) {
            $this->info('Tidak ada pengingat operasional untuk dikirim.');

            return self::SUCCESS;
        }

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('email')
            ->unique()
            ->values();

        if ($adminEmails->isEmpty()) {
            $this->warn('Tidak ada akun Administrator aktif dengan alamat email.');

            return self::FAILURE;
        }

        foreach ($adminEmails as $email) {
            Notification::route('mail', $email)->notify(new OperationalRemindersNotification(
                $overdueLoans,
                $maintenanceSchedules,
                $expiringWarranties,
                $maintenanceUntil,
                $warrantyUntil,
            ));
        }

        $this->info('Email pengingat dikirim kepada '.$adminEmails->count().' Administrator.');

        return self::SUCCESS;
    }
}
