<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Services\AssetDisposalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewAssetDisposal extends ViewRecord
{
    protected static string $resource = AssetDisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui Penghapusan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('notes')->label('Catatan Persetujuan')->maxLength(5000),
                ])
                ->visible(fn (): bool => $this->record->status === 'pending' && (auth()->user()?->isAdmin() ?? false))
                ->action(fn (array $data, AssetDisposalService $service) => $this->review(
                    fn () => $service->approve($this->record, auth()->user(), $data['notes'] ?? null),
                    'Penghapusan aset disetujui.',
                )),
            Action::make('reject')
                ->label('Tolak Pengajuan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->schema([
                    Textarea::make('notes')->label('Alasan Penolakan')->required()->maxLength(5000),
                ])
                ->visible(fn (): bool => $this->record->status === 'pending' && (auth()->user()?->isAdmin() ?? false))
                ->action(fn (array $data, AssetDisposalService $service) => $this->review(
                    fn () => $service->reject($this->record, auth()->user(), $data['notes']),
                    'Pengajuan penghapusan ditolak.',
                )),
            Action::make('document')
                ->label('Cetak Berita Acara')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('asset-disposals.document', ['disposal' => $this->record]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->status === 'approved'),
        ];
    }

    private function review(callable $operation, string $message): void
    {
        try {
            $operation();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Pengajuan belum dapat diperiksa')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->danger()
                ->send();

            return;
        }

        $this->record->refresh();
        Notification::make()->title($message)->success()->send();
    }
}
