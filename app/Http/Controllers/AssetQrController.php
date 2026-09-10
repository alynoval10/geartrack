<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\View\View;

class AssetQrController extends Controller
{
    public function show(string $token): View
    {
        $asset = Asset::with([
            'category',
            'brand',
            'location',
        ])
            ->where('qr_token', $token)
            ->firstOrFail();

        return view('assets.qr-detail', [
            'asset' => $asset,
        ]);
    }
}