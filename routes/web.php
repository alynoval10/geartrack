<?php

use App\Http\Controllers\AssetQrController;
use App\Http\Controllers\QrScannerController;
use Illuminate\Support\Facades\Route;

Route::get('/scan', [QrScannerController::class, 'index'])
    ->name('qr.scan');

Route::get('/q/{token}', [AssetQrController::class, 'show'])
    ->name('asset.qr.show');

Route::get('/q/{token}/label', [AssetQrController::class, 'label'])
    ->name('asset.qr.label');

Route::get('/labels/print', [AssetQrController::class, 'bulkLabel'])
    ->name('asset.qr.bulk-label');