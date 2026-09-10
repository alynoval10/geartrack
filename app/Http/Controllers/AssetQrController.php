<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

    public function label(string $token): View
    {
        $asset = Asset::with([
            'category',
            'brand',
            'location',
        ])
            ->where('qr_token', $token)
            ->firstOrFail();

        $url = route('asset.qr.show', [
            'token' => $asset->qr_token,
        ]);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return view('assets.qr-label', [
            'asset' => $asset,
            'qrCode' => $qrCode,
            'url' => $url,
        ]);
    }
}