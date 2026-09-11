<?php

namespace App\Filament\Resources\AssetSets\Pages;

use App\Filament\Resources\AssetSets\AssetSetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetSet extends EditRecord
{
    protected static string $resource = AssetSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
