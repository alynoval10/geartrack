<?php

use App\Http\Controllers\AssetQrController;
use Illuminate\Support\Facades\Route;

Route::get('/q/{token}', [AssetQrController::class, 'show'])
    ->name('asset.qr.show');