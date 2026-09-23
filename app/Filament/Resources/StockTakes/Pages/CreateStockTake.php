<?php

namespace App\Filament\Resources\StockTakes\Pages;

use App\Filament\Resources\StockTakes\StockTakeResource;
use App\Services\StockTakeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateStockTake extends CreateRecord
{
    protected static string $resource = StockTakeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(StockTakeService::class)->start($data, auth()->user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn ($errors, $field) => ['data.'.$field => $errors])->all()
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
