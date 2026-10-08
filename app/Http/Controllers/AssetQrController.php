<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\SchoolSetting;
use App\Services\LoanAssetEligibility;
use App\Services\MaintenanceAssetEligibility;
use App\Services\TransferAssetEligibility;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetQrController extends Controller
{
    public function show(
        string $token,
        Request $request,
        LoanAssetEligibility $loanAssets,
        TransferAssetEligibility $transferAssets,
        MaintenanceAssetEligibility $maintenanceAssets,
    ): View {
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

        $actionAvailability = [
            'loan' => $loanAssets->ineligibilityReason($asset),
            'transfer' => $transferAssets->ineligibilityReason($asset),
            'maintenance' => $maintenanceAssets->ineligibilityReason($asset),
        ];

        return view('assets.qr-detail', compact('asset', 'stockTakeId', 'actionAvailability'));
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

        $url = $this->assetPublicUrl($asset->qr_token);

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

        $assets->each(function ($asset): void {
            $url = $this->assetPublicUrl($asset->qr_token);

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

    public function bulkNiimbot(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('assets')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $assets = Asset::with(['category', 'brand', 'location', 'specifications'])
            ->whereIn('id', $ids)
            ->get();

        abort_if($assets->isEmpty(), 404);

        $assets->each(function (Asset $asset): void {
            $url = $this->assetPublicUrl($asset->qr_token);
            $asset->generatedQr = QrCode::format('svg')
                ->size(180)
                ->margin(0)
                ->errorCorrection('M')
                ->generate($url);
        });

        return view('assets.niimbot-bulk', ['assets' => $assets]);
    }

    /** @return array{labelWidth: int, labelHeight: int, labelPadding: float, labelGap: float, isStacked: bool, qrSize: float, brandFontSize: float, codeFontSize: float, nameFontSize: float, hintFontSize: float, lineGap: float} */
    private function labelLayout(): array
    {
        $setting = SchoolSetting::current();
        $labelWidth = $setting->qr_label_width_mm;
        $labelHeight = $setting->qr_label_height_mm;

        $labelPadding = round(max(1, min($labelWidth, $labelHeight) * 0.05), 1);
        $labelGap = $labelPadding;
        $contentWidth = $labelWidth - ($labelPadding * 2);
        $contentHeight = $labelHeight - ($labelPadding * 2);

        // Label yang mendekati persegi memakai susunan vertikal agar ruang tambahan tetap dipakai oleh QR dan teks.
        $isStacked = $labelHeight >= ($labelWidth * 0.9);
        $fontScale = $isStacked
            ? min($labelWidth / 45, $labelHeight / 50)
            : min($labelWidth / 50, $labelHeight / 30);

        $qrSize = round($isStacked
            ? min($contentWidth * 0.72, $contentHeight * 0.56)
            : min($contentHeight, $contentWidth * 0.54), 1);
        $brandFontSize = round(max(4.5, 6.5 * $fontScale), 1);
        $codeFontSize = round(max(5.5, 9 * $fontScale), 1);
        $nameFontSize = round(max(4.5, 6.5 * $fontScale), 1);
        $hintFontSize = round(max(4, 5 * $fontScale), 1);
        $lineGap = round(max(0.4, 0.7 * $fontScale), 1);

        return compact(
            'labelWidth',
            'labelHeight',
            'labelPadding',
            'labelGap',
            'isStacked',
            'qrSize',
            'brandFontSize',
            'codeFontSize',
            'nameFontSize',
            'hintFontSize',
            'lineGap',
        );
    }

    private function assetPublicUrl(string $token): string
    {
        // Domain publik tetap dipakai meskipun halaman dicetak dari IP lokal; tanpa konfigurasi, gunakan domain permintaan saat ini.
        $origin = rtrim((string) (config('app.public_url') ?: request()->getSchemeAndHttpHost()), '/');

        return $origin.route('asset.qr.show', ['token' => $token], false);
    }

    public function niimbot(string $token): View
    {
        $asset = Asset::with([
            'category',
            'brand',
            'location',
            'specifications',
        ])
            ->where('qr_token', $token)
            ->firstOrFail();

        $url = $this->assetPublicUrl($asset->qr_token);

        $qrCode = QrCode::format('svg')
            ->size(180)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($url);

        return view('assets.niimbot', [
            'asset' => $asset,
            'qrCode' => $qrCode,
            'url' => $url,
        ]);
    }
}
