<?php

use App\Http\Controllers\AssetQrController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\QrScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-backups', 'throttle:10,1'])->prefix('backups')->name('backups.')->group(function (): void {
    Route::post('/', [BackupController::class, 'store'])->name('store');
    Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
    Route::get('/{name}', [BackupController::class, 'download'])->name('download');
    Route::delete('/{name}', [BackupController::class, 'destroy'])->name('destroy');
});

Route::get('/scan', [QrScannerController::class, 'index'])
    ->name('qr.scan');

Route::get('/q/{token}', [AssetQrController::class, 'show'])
    ->name('asset.qr.show');

Route::get('/q/{token}/label', [AssetQrController::class, 'label'])
    ->name('asset.qr.label');

Route::get('/labels/print', [AssetQrController::class, 'bulkLabel'])
    ->name('asset.qr.bulk-label');
