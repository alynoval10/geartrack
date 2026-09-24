<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    #[On('loan-updated')]
    public function refreshLoan(): void
    {
        $this->record->refresh();
    }
}
