<?php

use App\Http\Controllers\AssetQrController;
use App\Http\Controllers\AssetTransferDocumentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\QrScannerController;
use Illuminate\Support\Facades\Route;

Route::get('/transfers/{transfer}/document', AssetTransferDocumentController::class)
    ->middleware('auth')->name('transfers.document');

Route::middleware('auth')->prefix('backup-actions')->group(function () {
    Route::post('/create', [BackupController::class, 'store'])
        ->name('backups.store');

    Route::get('/download/{name}', [BackupController::class, 'download'])
        ->name('backups.download');

    Route::delete('/delete/{name}', [BackupController::class, 'destroy'])
        ->name('backups.destroy');

    Route::post('/restore', [BackupController::class, 'restore'])
        ->name('backups.restore');
});

Route::get('/scan', [QrScannerController::class, 'index'])
    ->name('qr.scan');

Route::get('/q/{token}', [AssetQrController::class, 'show'])
    ->name('asset.qr.show');

Route::get('/q/{token}/label', [AssetQrController::class, 'label'])
    ->name('asset.qr.label');

Route::get('/labels/print', [AssetQrController::class, 'bulkLabel'])
    ->name('asset.qr.bulk-label');
