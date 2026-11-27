<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BorrowerQrController extends Controller
{
    public function show(string $token, Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $borrower = Borrower::query()
            ->where('type', 'student')
            ->where('qr_token', $token)
            ->firstOrFail();

        $url = route('borrowers.qr-card', ['token' => $borrower->qr_token]);
        $qrCode = QrCode::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return view('borrowers.qr-card', [
            'borrower' => $borrower,
            'schoolName' => SchoolSetting::current()->school_name,
            'qrCode' => $qrCode,
        ]);
    }
}
