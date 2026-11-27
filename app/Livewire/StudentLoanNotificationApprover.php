<?php

namespace App\Livewire;

use App\Models\StudentLoanRequest;
use App\Models\User;
use App\Services\StudentLoanRequestService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class StudentLoanNotificationApprover extends Component
{
    #[On('approve-student-loan-request')]
    public function approve(int $requestId, StudentLoanRequestService $service): void
    {
        $approver = auth()->user();
        abort_unless($approver instanceof User, 403);

        try {
            $loan = $service->approve(StudentLoanRequest::query()->findOrFail($requestId), $approver);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Permintaan belum dapat disetujui')
                ->body(collect($exception->errors())->flatten()->first())
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Peminjaman disetujui')
            ->body('Transaksi '.$loan->code.' sudah masuk ke menu Peminjaman. '.$approver->name.' tercatat sebagai penanggung jawab.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.student-loan-notification-approver');
    }
}
