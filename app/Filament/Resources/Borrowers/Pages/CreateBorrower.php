<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBorrower extends CreateRecord
{
    protected static string $resource = BorrowerResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
