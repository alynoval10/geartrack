<?php

namespace App\Notifications;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\MaintenanceSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OperationalRemindersNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, Loan>  $overdueLoans
     * @param  Collection<int, MaintenanceSchedule>  $maintenanceSchedules
     * @param  Collection<int, Asset>  $expiringWarranties
     */
    public function __construct(
        private readonly Collection $overdueLoans,
        private readonly Collection $maintenanceSchedules,
        private readonly Collection $expiringWarranties,
        private readonly Carbon $maintenanceUntil,
        private readonly Carbon $warrantyUntil,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Pengingat Operasional GearTrack')
            ->greeting('Pengingat Operasional GearTrack')
            ->line('Berikut ringkasan yang memerlukan perhatian:');

        if ($this->overdueLoans->isNotEmpty()) {
            $message->line('Peminjaman terlambat ('.$this->overdueLoans->count().'):');
            foreach ($this->overdueLoans as $loan) {
                $message->line('- '.$loan->borrower_name.' | batas '.$loan->due_date->format('d M Y').' | '.$loan->code);
            }
        }

        if ($this->maintenanceSchedules->isNotEmpty()) {
            $message->line('Jadwal perawatan sampai '.$this->maintenanceUntil->format('d M Y').' ('.$this->maintenanceSchedules->count().'):');
            foreach ($this->maintenanceSchedules as $schedule) {
                $message->line('- '.($schedule->asset?->asset_code ?? $schedule->asset_code).' — '.($schedule->asset?->name ?? $schedule->asset_name).' | '.$schedule->title.' | '.$schedule->due_date->format('d M Y'));
            }
        }

        if ($this->expiringWarranties->isNotEmpty()) {
            $message->line('Garansi berakhir sampai '.$this->warrantyUntil->format('d M Y').' ('.$this->expiringWarranties->count().'):');
            foreach ($this->expiringWarranties as $asset) {
                $message->line('- '.$asset->asset_code.' — '.$asset->name.' | '.$asset->warranty_end_date->format('d M Y'));
            }
        }

        return $message->line('Email ini dikirim otomatis oleh GearTrack.');
    }
}
