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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

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

    /**
     * Isi pilihan aset pada modal kaitkan dari label QR yang dipindai.
     */
    #[On('asset-set-member-scanned')]
    public function selectScannedAsset(string $token): void
    {
        if (! Str::isUuid($token)) {
            $this->sendScanResult(false, 'QR tidak dikenali sebagai label aset GearTrack.');

            return;
        }

        $asset = Asset::query()
            ->with('assetSet:id,name')
            ->where('qr_token', $token)
            ->first();

        if (! $asset) {
            $this->sendScanResult(false, 'Aset tidak ditemukan. Periksa kembali label QR yang dipindai.');

            return;
        }

        if ($asset->asset_set_id !== null) {
            $packageName = $asset->asset_set_id === $this->getOwnerRecord()->getKey()
                ? 'paket ini'
                : 'paket '.$asset->assetSet?->name;

            $this->sendScanResult(false, $asset->asset_code.' sudah menjadi anggota '.$packageName.'.');

            return;
        }

        $mountedAction = $this->getMountedAction();
        if ($mountedAction?->getName() !== 'associate') {
            $this->sendScanResult(false, 'Buka kembali menu Tambahkan Aset, lalu ulangi pemindaian.');

            return;
        }

        // Pertahankan peran yang mungkin sudah dipilih ketika hasil scan mengisi pilihan aset.
        $this->getMountedActionSchema()?->fill([
            ...$mountedAction->getData(),
            'recordId' => $asset->getKey(),
        ]);

        $this->sendScanResult(true, $asset->asset_code.' — '.$asset->name.' dipilih. Tentukan perannya, lalu klik Kaitkan.');
    }

    private function sendScanResult(bool $success, string $message): void
    {
        $this->dispatch('asset-set-member-scan-result', success: $success, message: $message);
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
                    ->modalDescription('Pilih aset secara manual atau scan label QR, lalu tentukan perannya dalam paket.')
                    ->modalContent(fn () => view('filament.resources.asset-sets.member-qr-scanner'))
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
