<?php

namespace App\Filament\Resources\AssetHistories\Pages;

use App\Filament\Resources\AssetHistories\AssetHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListAssetHistories extends ListRecords
{
    protected static string $resource = AssetHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
