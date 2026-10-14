<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use App\Models\LoanItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

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
            Action::make('printReport')
                ->label('Cetak Data Peminjaman')
                ->icon('heroicon-o-printer')
                ->url(route('filament.admin.pages.reports', ['type' => 'loans']))
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            CreateAction::make(),
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
}
