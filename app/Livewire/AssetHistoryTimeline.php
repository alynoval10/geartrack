<?php

namespace App\Livewire;

use App\Models\Asset;
use Filament\Widgets\Widget;

class AssetHistoryTimeline extends Widget
{
    protected string $view = 'livewire.asset-history-timeline';

    public ?Asset $record = null;

    protected int|string|array $columnSpan = 'full';

    public function getHistoriesProperty()
    {
        if (! $this->record) {
            return collect();
        }

        return $this->record
            ->histories()
            ->with('user')
            ->get();
    }
}