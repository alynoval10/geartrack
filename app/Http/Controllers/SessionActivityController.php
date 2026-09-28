<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforceIdleSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionActivityController extends Controller
{
    /**
     * Menyimpan aktivitas nyata dari browser tanpa memuat ulang halaman Filament.
     */
    public function __invoke(Request $request): Response
    {
        $request->session()->put(
            EnforceIdleSession::LAST_ACTIVITY_KEY,
            now()->timestamp,
        );

        return response()->noContent();
    }
}
