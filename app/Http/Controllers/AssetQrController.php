<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetQrController extends Controller
{
    public function show(string $token, Request $request): View
    {
        $asset = Asset::with([
            'category',
            'brand',
            'location',
            'specifications',
            'assetSet.location',
            'assetSet.assets',
        ])
            ->where('qr_token', $token)
            ->firstOrFail();

        $stockTakeId = filter_var($request->query('stock_take'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

        return view('assets.qr-detail', compact('asset', 'stockTakeId'));
    }

    public function label(string $token, Request $request): View
    {
        $asset = Asset::with([
            'category',
            'brand',
            'location',
            'specifications',
        ])
            ->where('qr_token', $token)
            ->firstOrFail();

        $url = rtrim(config('app.url'), '/').route(
            'asset.qr.show',
            ['token' => $asset->qr_token],
            false
        );

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        $view = $request->query('paper') === 'a4' ? 'assets.qr-label-a4' : 'assets.qr-label';

        return view($view, [
            'asset' => $asset,
            'qrCode' => $qrCode,
            'url' => $url,
            ...$this->labelLayout(),
        ]);
    }

    public function bulkLabel(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('assets')))
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

        $view = $request->query('paper') === 'a4' ? 'assets.qr-labels' : 'assets.qr-labels-printer';

        return view($view, [
            'assets' => $assets,
            'selection' => $ids->implode(','),
            ...$this->labelLayout(),
        ]);
    }

    /** @return array{labelWidth: int, labelHeight: int, qrSize: float, brandFontSize: float, codeFontSize: float, nameFontSize: float, hintFontSize: float} */
    private function labelLayout(): array
    {
        $setting = SchoolSetting::current();
        $labelWidth = $setting->qr_label_width_mm;
        $labelHeight = $setting->qr_label_height_mm;

        // Sisakan ruang aman di sekeliling QR agar tetap mudah dipindai pada ukuran label yang berbeda.
        $qrSize = round(max(14, min($labelHeight - 5, $labelWidth * 0.46)), 1);
        $brandFontSize = round(max(5.5, min(9, $labelHeight * 0.22)), 1);
        $codeFontSize = round(max(6.5, min(13, $labelHeight * 0.3)), 1);
        $nameFontSize = round(max(5.5, min(10, $labelHeight * 0.23)), 1);
        $hintFontSize = round(max(5, min(8, $labelHeight * 0.18)), 1);

        return compact(
            'labelWidth',
            'labelHeight',
            'qrSize',
            'brandFontSize',
            'codeFontSize',
            'nameFontSize',
            'hintFontSize',
        );
    }
}
