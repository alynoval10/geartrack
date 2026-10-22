<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn (): int => AssetResource::getEloquentQuery()->count()),
            'available' => Tab::make('Tersedia')
                ->badge(fn (): int => AssetResource::getEloquentQuery()->where('status', 'available')->count())
                ->query(fn (Builder $query): Builder => $query->where('status', 'available')),
            'borrowed' => Tab::make('Dipinjam')
                ->badge(fn (): int => AssetResource::getEloquentQuery()->where('status', 'borrowed')->count())
                ->query(fn (Builder $query): Builder => $query->where('status', 'borrowed')),
            'damaged' => Tab::make('Rusak')
                ->badge(fn (): int => AssetResource::getEloquentQuery()->whereIn('condition', ['minor_damage', 'major_damage'])->count())
                ->query(fn (Builder $query): Builder => $query->whereIn('condition', ['minor_damage', 'major_damage'])),
            'maintenance' => Tab::make('Perawatan')
                ->badge(fn (): int => AssetResource::getEloquentQuery()->where('status', 'maintenance')->count())
                ->query(fn (Builder $query): Builder => $query->where('status', 'maintenance')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
