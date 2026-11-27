<?php

namespace App\Services;

use App\Filament\Pages\StudentLoanApprovals;
use App\Models\Asset;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\SchoolSetting;
use App\Models\StudentAccount;
use App\Models\StudentLoanRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLoanRequestService
{
    public function submit(StudentAccount $account, Borrower $borrower, array $data): StudentLoanRequest
    {
        $academicYear = SchoolSetting::query()->value('academic_year');
        $ids = collect($data['asset_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();

        $loanRequest = DB::transaction(function () use ($account, $borrower, $data, $academicYear, $ids): StudentLoanRequest {
            $currentBorrower = Borrower::query()
                ->whereKey($borrower->id)
                ->where('type', 'student')
                ->where('nis', $account->nis)
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $currentBorrower || blank($academicYear)) {
                throw ValidationException::withMessages(['asset_ids' => 'Data siswa tidak aktif pada tahun ajaran berjalan. Silakan masuk kembali.']);
            }

            $assets = Asset::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($ids === [] || $assets->count() !== count($ids)) {
                throw ValidationException::withMessages(['asset_ids' => 'Sebagian perangkat sudah tidak tersedia. Muat ulang halaman dan pilih kembali.']);
            }

            foreach ($assets as $asset) {
                if (! in_array($asset->status, ['available', 'in_use'], true)
                    || $asset->condition !== 'good'
                    || $asset->loanItems()->whereNotNull('active_asset_id')->exists()
                    || $asset->maintenanceReports()->whereIn('status', ['open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['asset_ids' => $asset->asset_code.' tidak tersedia untuk dipinjam.']);
                }
            }

            $loanRequest = StudentLoanRequest::query()->create([
                'student_account_id' => $account->id,
                'borrower_id' => $currentBorrower->id,
                'borrower_name' => $currentBorrower->name,
                'borrower_nis' => $currentBorrower->nis,
                'borrower_class' => trim($currentBorrower->class_level.' '.$currentBorrower->class_name),
                'academic_year' => $currentBorrower->academic_year,
                'purpose' => $data['purpose'],
                'due_date' => $data['due_date'],
                'status' => 'pending',
            ]);

            foreach ($assets as $asset) {
                $loanRequest->items()->create([
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name,
                ]);
            }

            return $loanRequest;
        });

        // Notifications are stored for every active school operator after the request is committed.
        User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (User $user) => $user->notifyNow(
                Notification::make()
                    ->title('Permintaan peminjaman siswa baru')
                    ->body($loanRequest->borrower_name.' ('.$loanRequest->borrower_class.') meminta '.count($ids).' perangkat.')
                    ->actions([
                        Action::make('approve')
                            ->label('Setujui & Buat Peminjaman')
                            ->button()
                            ->color('success')
                            ->dispatch('approve-student-loan-request', ['requestId' => $loanRequest->id])
                            ->close()
                            ->markAsRead(),
                        Action::make('review')
                            ->label('Tinjau Detail')
                            ->color('gray')
                            ->url(StudentLoanApprovals::getUrl(['request' => $loanRequest->id]))
                            ->markAsRead(),
                    ])
                    ->toDatabase(),
            ));

        return $loanRequest;
    }

    public function approve(StudentLoanRequest $loanRequest, User $reviewer): Loan
    {
        return DB::transaction(function () use ($loanRequest, $reviewer): Loan {
            $lockedRequest = StudentLoanRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($loanRequest->id);

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Permintaan ini sudah diproses.']);
            }

            $borrower = $lockedRequest->borrower;
            $academicYear = SchoolSetting::query()->value('academic_year');

            if (! $borrower
                || ! $borrower->is_active
                || $borrower->academic_year !== $academicYear
                || $borrower->nis !== $lockedRequest->borrower_nis) {
                throw ValidationException::withMessages(['request' => 'Siswa tidak lagi aktif pada tahun ajaran berjalan. Permintaan tidak dapat disetujui.']);
            }

            $assetIds = $lockedRequest->items->pluck('asset_id')->filter()->map(fn (mixed $id): int => (int) $id)->all();

            if (count($assetIds) !== $lockedRequest->items->count()) {
                throw ValidationException::withMessages(['request' => 'Sebagian perangkat dalam permintaan sudah dihapus. Permintaan tidak dapat disetujui.']);
            }

            $loan = app(LoanService::class)->borrow([
                'borrower_type' => 'student',
                'borrower_id' => $borrower->id,
                'borrower_level' => $borrower->class_level,
                'borrower_class' => $borrower->class_name,
                'responsible_user_id' => $reviewer->id,
                'selection_type' => 'asset',
                'purpose' => $lockedRequest->purpose,
                'due_date' => $lockedRequest->due_date->toDateString(),
                'asset_ids' => $assetIds,
            ], $reviewer);

            $lockedRequest->update([
                'loan_id' => $loan->id,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'status' => 'approved',
                'review_notes' => null,
            ]);

            $this->removeRequestNotifications($lockedRequest->id);

            return $loan;
        });
    }

    public function reject(StudentLoanRequest $loanRequest, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($loanRequest, $reviewer, $reason): void {
            $lockedRequest = StudentLoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Permintaan ini sudah diproses.']);
            }

            $lockedRequest->update([
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'status' => 'rejected',
                'review_notes' => $reason,
            ]);

            $this->removeRequestNotifications($lockedRequest->id);
        });
    }

    private function removeRequestNotifications(int $requestId): void
    {
        DatabaseNotificationModel::query()
            ->where('type', DatabaseNotification::class)
            ->where('data->actions[0]->eventData->requestId', $requestId)
            ->delete();
    }
}
