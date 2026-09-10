<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
       protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}

protected function getPreviousUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
