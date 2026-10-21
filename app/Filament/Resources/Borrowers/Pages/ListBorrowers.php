<?php

namespace App\Filament\Resources\Borrowers\Pages;

use App\Filament\Resources\Borrowers\BorrowerResource;
use App\Services\BorrowerImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListBorrowers extends ListRecords
{
    protected static string $resource = BorrowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Unduh Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('borrowers.template')),
            Action::make('import')
                ->label('Impor Data')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/zip',
                        ])
                        ->maxSize(5120)
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Maksimal 1.000 baris dan 5 MB. Gunakan sheet Data Peminjam dari template GearTrack.'),
                ])
                ->action(function (array $data): void {
                    $path = $data['file']->getRealPath();
                    if (! is_string($path)) {
                        throw ValidationException::withMessages(['file' => 'File yang diunggah tidak dapat dibaca.']);
                    }

                    $result = app(BorrowerImportService::class)->import($path);
                    Notification::make()
                        ->title($result['created'].' data ditambahkan, '.$result['updated'].' data diperbarui')
                        ->success()
                        ->duration(6000)
                        ->send();
                }),
            CreateAction::make()->label('Tambah Data Peminjam'),
        ];
    }
}
