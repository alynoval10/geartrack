<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Layanan menjaga agar admin terakhir dan sesi login tidak dapat terlewati.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UserManagementService::class)->save($data, $record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus User')
                // Penghapusan tetap melewati perlindungan admin terakhir dan pencabutan sesi.
                ->using(fn (User $record): bool => app(UserManagementService::class)->delete($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
