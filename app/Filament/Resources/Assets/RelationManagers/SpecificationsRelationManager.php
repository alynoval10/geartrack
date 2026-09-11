<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextInputColumn;

class SpecificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'specifications';

    protected static ?string $title = 'Spesifikasi';

    protected static ?string $modelLabel = 'Spesifikasi';

    protected static ?string $pluralModelLabel = 'Spesifikasi';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Nama Spesifikasi')
                    ->placeholder('Contoh: Processor')
                    ->required()
                    ->maxLength(100),

                TextInput::make('key')
                    ->label('Key')
                    ->placeholder('Contoh: processor')
                    ->required()
                    ->maxLength(100)
                    ->helperText(
                        'Gunakan huruf kecil tanpa spasi, misalnya processor, ram, storage.'
                    ),

                Textarea::make('value')
                    ->label('Nilai')
                    ->placeholder('Contoh: Intel Core i5-10400')
                    ->rows(2),

                Hidden::make('sort')
                    ->default(999),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')
                    ->label('Spesifikasi')
                    ->weight('bold'),

                TextInputColumn::make('value')
                    ->label('Nilai')
                    ->placeholder('Klik untuk mengisi')
                    ->extraInputAttributes([
                        'style' => 'min-width: 250px;',
                    ]),
            ])

            ->defaultSort('sort')
            ->headerActions([
                Action::make('applyPreset')
                    ->label('Siapkan Spesifikasi Otomatis')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->action(fn () => $this->applyPreset()),

                CreateAction::make()
                    ->label('Tambah Spesifikasi Lain'),
            ])

            ->recordActions([
                DeleteAction::make()
                    ->label('Hapus'),
            ])


            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public function applyPreset(): void
    {
        $asset = $this->getOwnerRecord();

        $asset->loadMissing('category');

        if (! $asset->category) {
            Notification::make()
                ->title('Kategori aset belum ditentukan')
                ->warning()
                ->send();

            return;
        }

        $preset = $this->getPreset(
            $asset->category->asset_prefix,
            $asset->category->name,
        );

        if (empty($preset)) {
            Notification::make()
                ->title('Preset belum tersedia')
                ->body(
                    'Belum ada preset spesifikasi untuk kategori '
                    . $asset->category->name
                    . '.'
                )
                ->warning()
                ->send();

            return;
        }

        $added = 0;

        foreach ($preset as $item) {
            $specification = $asset->specifications()->firstOrCreate(
                [
                    'key' => $item['key'],
                ],
                [
                    'label' => $item['label'],
                    'value' => null,
                    'sort' => $item['sort'],
                ]
            );

            if ($specification->wasRecentlyCreated) {
                $added++;
            }
        }

        if ($added === 0) {
            Notification::make()
                ->title('Spesifikasi sudah tersedia')
                ->body('Tidak ada spesifikasi baru yang perlu ditambahkan.')
                ->info()
                ->send();

            return;
        }

        Notification::make()
            ->title('Spesifikasi berhasil disiapkan')
            ->body(
                $added . ' spesifikasi sesuai kategori berhasil ditambahkan. '
                . 'Silakan isi nilainya langsung pada kolom Nilai.'
            )
            ->success()
            ->send();
    }

    private function getPreset(
        ?string $prefix,
        ?string $categoryName
    ): array {
        $prefix = strtoupper(trim((string) $prefix));
        $name = strtolower(trim((string) $categoryName));

        /*
         * PC / Komputer Desktop
         */
        if (
            in_array($prefix, ['PC', 'CPU', 'DT'], true)
            || str_contains($name, 'komputer')
            || str_contains($name, 'desktop')
        ) {
            return [
                [
                    'key' => 'processor',
                    'label' => 'Processor',
                    'sort' => 10,
                ],
                [
                    'key' => 'ram',
                    'label' => 'RAM',
                    'sort' => 20,
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage',
                    'sort' => 30,
                ],
                [
                    'key' => 'graphics',
                    'label' => 'Graphics / VGA',
                    'sort' => 40,
                ],
                [
                    'key' => 'operating_system',
                    'label' => 'Sistem Operasi',
                    'sort' => 50,
                ],
                [
                    'key' => 'hostname',
                    'label' => 'Hostname',
                    'sort' => 60,
                ],
            ];
        }

        /*
         * Router
         */
        if (
            in_array($prefix, ['RT', 'RTR', 'ROUTER'], true)
            || str_contains($name, 'router')
        ) {
            return [
                [
                    'key' => 'port_count',
                    'label' => 'Jumlah Port',
                    'sort' => 10,
                ],
                [
                    'key' => 'firmware',
                    'label' => 'RouterOS / Firmware',
                    'sort' => 20,
                ],
                [
                    'key' => 'management_ip',
                    'label' => 'IP Manajemen',
                    'sort' => 30,
                ],
                [
                    'key' => 'mac_address',
                    'label' => 'MAC Address',
                    'sort' => 40,
                ],
            ];
        }

        /*
         * Switch
         */
        if (
            in_array($prefix, ['SW', 'SWT'], true)
            || str_contains($name, 'switch')
        ) {
            return [
                [
                    'key' => 'port_count',
                    'label' => 'Jumlah Port',
                    'sort' => 10,
                ],
                [
                    'key' => 'switch_type',
                    'label' => 'Tipe Switch',
                    'sort' => 20,
                ],
                [
                    'key' => 'poe',
                    'label' => 'PoE',
                    'sort' => 30,
                ],
                [
                    'key' => 'management_ip',
                    'label' => 'IP Manajemen',
                    'sort' => 40,
                ],
                [
                    'key' => 'firmware',
                    'label' => 'Firmware',
                    'sort' => 50,
                ],
            ];
        }

        /*
         * Access Point
         */
        if (
            in_array($prefix, ['AP', 'WAP'], true)
            || str_contains($name, 'access point')
        ) {
            return [
                [
                    'key' => 'wifi_standard',
                    'label' => 'Standar Wi-Fi',
                    'sort' => 10,
                ],
                [
                    'key' => 'frequency_band',
                    'label' => 'Band / Frekuensi',
                    'sort' => 20,
                ],
                [
                    'key' => 'ssid',
                    'label' => 'SSID',
                    'sort' => 30,
                ],
                [
                    'key' => 'management_ip',
                    'label' => 'IP Manajemen',
                    'sort' => 40,
                ],
                [
                    'key' => 'poe',
                    'label' => 'PoE',
                    'sort' => 50,
                ],
            ];
        }

        /*
         * IP Camera / CCTV
         */
        if (
            in_array($prefix, ['CAM', 'CCTV', 'IPC'], true)
            || str_contains($name, 'camera')
            || str_contains($name, 'kamera')
            || str_contains($name, 'cctv')
        ) {
            return [
                [
                    'key' => 'resolution',
                    'label' => 'Resolusi',
                    'sort' => 10,
                ],
                [
                    'key' => 'lens',
                    'label' => 'Lensa',
                    'sort' => 20,
                ],
                [
                    'key' => 'ip_address',
                    'label' => 'IP Address',
                    'sort' => 30,
                ],
                [
                    'key' => 'poe',
                    'label' => 'PoE',
                    'sort' => 40,
                ],
                [
                    'key' => 'onvif',
                    'label' => 'ONVIF',
                    'sort' => 50,
                ],
            ];
        }

        /*
         * NVR
         */
        if (
            $prefix === 'NVR'
            || str_contains($name, 'nvr')
        ) {
            return [
                [
                    'key' => 'channel_count',
                    'label' => 'Jumlah Channel',
                    'sort' => 10,
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage',
                    'sort' => 20,
                ],
                [
                    'key' => 'ip_address',
                    'label' => 'IP Address',
                    'sort' => 30,
                ],
                [
                    'key' => 'firmware',
                    'label' => 'Firmware',
                    'sort' => 40,
                ],
            ];
        }

        /*
         * Server
         */
        if (
            in_array($prefix, ['SRV', 'SERVER'], true)
            || str_contains($name, 'server')
        ) {
            return [
                [
                    'key' => 'processor',
                    'label' => 'Processor',
                    'sort' => 10,
                ],
                [
                    'key' => 'ram',
                    'label' => 'RAM',
                    'sort' => 20,
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage',
                    'sort' => 30,
                ],
                [
                    'key' => 'raid',
                    'label' => 'RAID',
                    'sort' => 40,
                ],
                [
                    'key' => 'operating_system',
                    'label' => 'Sistem Operasi / Hypervisor',
                    'sort' => 50,
                ],
                [
                    'key' => 'ip_address',
                    'label' => 'IP Address',
                    'sort' => 60,
                ],
            ];
        }

        /*
         * Laptop
         */
        if (
            in_array($prefix, ['LT', 'LTP', 'NB'], true)
            || str_contains($name, 'laptop')
            || str_contains($name, 'notebook')
        ) {
            return [
                [
                    'key' => 'processor',
                    'label' => 'Processor',
                    'sort' => 10,
                ],
                [
                    'key' => 'ram',
                    'label' => 'RAM',
                    'sort' => 20,
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage',
                    'sort' => 30,
                ],
                [
                    'key' => 'screen',
                    'label' => 'Layar',
                    'sort' => 40,
                ],
                [
                    'key' => 'operating_system',
                    'label' => 'Sistem Operasi',
                    'sort' => 50,
                ],
            ];
        }

        return [];
    }


    
}