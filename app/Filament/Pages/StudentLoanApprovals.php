<?php

namespace App\Filament\Pages;

use App\Models\StudentLoanRequest;
use App\Models\User;
use App\Services\StudentLoanRequestService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use UnitEnum;

class StudentLoanApprovals extends Page
{
    protected string $view = 'filament.pages.student-loan-approvals';

    protected static ?string $title = 'Persetujuan Peminjaman';

    protected static ?string $navigationLabel = 'Persetujuan Peminjaman';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    /** @var array<int, string> */
    public array $reviewNotes = [];

    #[Url(as: 'request')]
    public ?int $focusedRequestId = null;

    public static function getNavigationBadge(): ?string
    {
        $pending = StudentLoanRequest::query()->where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    protected function getViewData(): array
    {
        return [
            'requests' => StudentLoanRequest::query()
                ->with(['items', 'loan', 'reviewer'])
                ->when($this->focusedRequestId, fn ($query) => $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$this->focusedRequestId]))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest()
                ->paginate(20),
        ];
    }

    public function approve(int $requestId, StudentLoanRequestService $service): void
    {
        $reviewer = auth()->user();
        abort_unless($reviewer instanceof User, 403);
        $loanRequest = StudentLoanRequest::query()->findOrFail($requestId);

        try {
            $loan = $service->approve($loanRequest, $reviewer);
        } catch (ValidationException $exception) {
            Notification::make()->title('Permintaan belum dapat disetujui')->body(collect($exception->errors())->flatten()->first())->warning()->send();

            return;
        }

        Notification::make()
            ->title('Peminjaman disetujui')
            ->body('Transaksi '.$loan->code.' sudah masuk ke menu Peminjaman. '.$reviewer->name.' tercatat sebagai penanggung jawab.')
            ->success()
            ->send();
    }

    public function reject(int $requestId, StudentLoanRequestService $service): void
    {
        $reviewer = auth()->user();
        abort_unless($reviewer instanceof User, 403);
        $reason = trim($this->reviewNotes[$requestId] ?? '');

        if ($reason === '' || mb_strlen($reason) > 2000) {
            Notification::make()->title('Alasan penolakan wajib diisi')->body('Isi alasan maksimal 2.000 karakter sebelum menolak.')->warning()->send();

            return;
        }

        try {
            $service->reject(StudentLoanRequest::query()->findOrFail($requestId), $reviewer, $reason);
        } catch (ValidationException $exception) {
            Notification::make()->title('Permintaan belum dapat ditolak')->body(collect($exception->errors())->flatten()->first())->warning()->send();

            return;
        }

        unset($this->reviewNotes[$requestId]);
        Notification::make()->title('Permintaan ditolak')->success()->send();
    }
}
