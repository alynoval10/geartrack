<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetQrController extends Controller
{
            public function show(string $token)
        {
            $asset = \App\Models\Asset::with([
                'category',
                'brand',
                'location',
                'specifications',
            ])
                ->where('qr_token', $token)
                ->firstOrFail();

            return view('assets.qr-detail', compact('asset'));
        }

    public function label(string $token): View
{
    $asset = Asset::with([
    'category',
    'brand',
    'location',
    'specifications',
    ])
    ->where('qr_token', $token)
    ->firstOrFail();

    $url = rtrim(config('app.url'), '/') . route(
    'asset.qr.show',
    ['token' => $asset->qr_token],
    false
);

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

    public function bulkLabel()
{
    $ids = collect(explode(',', request('assets')))
        ->filter()
        ->map(fn ($id) => (int) $id);

    abort_if($ids->isEmpty(), 404);

    $assets = Asset::with(['brand', 'category', 'location'])
        ->whereIn('id', $ids)
        ->get();

    abort_if($assets->isEmpty(), 404);

    $assets->each(function ($asset) {
        $url = route('asset.qr.show', [
            'token' => $asset->qr_token,
        ]);

        $asset->generatedQr = QrCode::format('svg')
            ->size(250)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);
    });

    return view('assets.qr-labels', [
        'assets' => $assets,
    ]);
}
}