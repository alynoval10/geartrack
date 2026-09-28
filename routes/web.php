<?php

use App\Http\Controllers\AssetImportController;
use App\Http\Controllers\AssetQrController;
use App\Http\Controllers\AssetTransferDocumentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\QrScannerController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\SessionActivityController;
use App\Http\Middleware\EnforceIdleSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/reports/export', InventoryReportController::class)->middleware('auth')->name('reports.export');

Route::post('/session/activity', SessionActivityController::class)
    ->middleware(['auth', EnforceIdleSession::class])
    ->name('session.activity');

Route::post('/school-settings', SchoolSettingController::class)
    ->middleware('auth')
    ->name('school-settings.update');

Route::middleware('auth')->prefix('asset-import')->name('asset-import.')->group(function (): void {
    Route::get('/template', [AssetImportController::class, 'template'])->name('template');
    Route::post('/preview', [AssetImportController::class, 'preview'])->name('preview');
    Route::post('/store', [AssetImportController::class, 'store'])->name('store');
    Route::post('/cancel', [AssetImportController::class, 'cancel'])->name('cancel');
});

Route::get('/transfers/{transfer}/document', AssetTransferDocumentController::class)
    ->middleware('auth')->name('transfers.document');

Route::middleware('auth')->prefix('backup-actions')->group(function () {
    Route::post('/create', [BackupController::class, 'store'])
        ->name('backups.store');

    Route::get('/download/{name}', [BackupController::class, 'download'])
        ->name('backups.download');

    Route::delete('/delete/{name}', [BackupController::class, 'destroy'])
        ->name('backups.destroy');

    Route::get('/delete/{name}', fn (): RedirectResponse => redirect()->route('filament.admin.pages.backups'));

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
