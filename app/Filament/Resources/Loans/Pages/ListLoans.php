<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printReport')
                ->label('Cetak Data Peminjaman')
                ->icon('heroicon-o-printer')
                ->url(route('filament.admin.pages.reports', ['type' => 'loans'])),
            CreateAction::make(),
        ];
    }
}
