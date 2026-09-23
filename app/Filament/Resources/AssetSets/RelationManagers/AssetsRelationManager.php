<?php

namespace App\Filament\Resources\AssetSets\RelationManagers;

use App\Models\Asset;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    protected static ?string $title = 'Anggota Paket';

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Aset';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->recordTitle(fn (Asset $record): string => "{$record->asset_code} — {$record->name}")
            ->emptyStateHeading('Belum ada anggota paket')
            ->emptyStateDescription('Tambahkan PC, monitor, atau perangkat lain, lalu tentukan perannya.')
            ->columns([
                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Aset'),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->placeholder('-'),

                SelectColumn::make('set_role')
                    ->label('Peran Dalam Paket')
                    ->options(Asset::SET_ROLES)
                    ->rules(['required', Rule::in(array_keys(Asset::SET_ROLES))])
                    ->placeholder('Pilih peran'),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->label('Tambahkan Aset')
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->whereNull('asset_set_id'))
                    ->schema(fn (AssociateAction $action): array => [
                        $action->getRecordSelect()->label('Aset')->helperText('Hanya aset yang belum menjadi anggota paket lain.'),
                        Select::make('set_role')->label('Peran Dalam Paket')
                            ->options(Asset::SET_ROLES)->required(),
                    ])
                    ->using(function (Asset $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $asset = Asset::query()->lockForUpdate()->findOrFail($record->getKey());
                            if ($asset->asset_set_id !== null) {
                                throw ValidationException::withMessages(['recordId' => 'Aset sudah menjadi anggota paket lain.']);
                            }
                            $asset->assetSet()->associate($this->getOwnerRecord());
                            $asset->set_role = $data['set_role'];
                            $asset->save();
                        });
                    })
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns([
                        'asset_code',
                        'name',
                    ]),
            ])
            ->recordActions([
                DissociateAction::make()
                    ->label('Keluarkan dari Paket'),
            ]);
    }
}
