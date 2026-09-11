<?php

namespace App\Filament\Resources\AssetSets\Pages;

use App\Filament\Resources\AssetSets\AssetSetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetSet extends ViewRecord
{
    protected static string $resource = AssetSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
