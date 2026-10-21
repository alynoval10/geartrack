<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use Filament\Resources\Pages\EditRecord;

class EditBorrower extends EditRecord
{
    protected static string $resource = BorrowerResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
