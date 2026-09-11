<?php

namespace App\Filament\Resources\AssetSets\Pages;

use App\Filament\Resources\AssetSets\AssetSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetSets extends ListRecords
{
    protected static string $resource = AssetSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
