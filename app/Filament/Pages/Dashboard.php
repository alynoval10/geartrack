<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Widgets\AssetStats;
use App\Filament\Widgets\LatestAssets;
use App\Filament\Widgets\MaintenanceReminders;
use App\Filament\Widgets\OperationalAlerts;
use App\Filament\Widgets\UserTasks;
use App\Models\LoanItem;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanLoanReturn')
                ->label('Scan Pengembalian')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->modalHeading('Scan QR Barang yang Dikembalikan')
                ->modalDescription('Pindai label aset untuk membuka transaksi peminjaman terkait. Tombol penerimaan tersedia pada rincian barang.')
                ->modalContent(fn () => view('filament.resources.loans.loan-return-qr-scanner'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    #[On('loan-return-asset-scanned')]
    public function findLoanForReturn(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->dispatch('loan-return-scan-result', success: false, message: 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $loanItem = LoanItem::query()
            ->with('loan')
            ->whereNotNull('active_asset_id')
            ->whereHas('asset', fn (Builder $query): Builder => $query->where('qr_token', $token))
            ->first();

        if (! $loanItem || ! $loanItem->loan) {
            $this->dispatch('loan-return-scan-result', success: false, message: 'Barang ini tidak ditemukan dalam peminjaman yang masih aktif.');

            return;
        }

        if (! auth()->user()?->can('receive', $loanItem->loan)) {
            $this->dispatch('loan-return-scan-result', success: false, message: 'Anda tidak memiliki akses untuk menerima pengembalian pada transaksi ini.');

            return;
        }

        $this->dispatch(
            'loan-return-scan-result',
            success: true,
            message: 'Transaksi ditemukan. Membuka rincian peminjaman…',
            destination: LoanResource::getUrl('view', ['record' => $loanItem->loan]),
        );
    }

    public function getHeader(): ?View
    {
        if (! $this->getCachedHeaderActions()) {
            $this->cacheInteractsWithHeaderActions();
        }

        return view('filament.pages.dashboard-header');
    }

    public function getWidgets(): array
    {
        return [
            UserTasks::class,
            OperationalAlerts::class,
            AssetStats::class,
            MaintenanceReminders::class,
            LatestAssets::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }
}
