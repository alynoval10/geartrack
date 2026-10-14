<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnforceIdleSession;
use App\Models\SchoolSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionActivityController extends Controller
{
    /**
     * Menyimpan aktivitas nyata dari browser tanpa memuat ulang halaman Filament.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->session()->put(
            EnforceIdleSession::LAST_ACTIVITY_KEY,
            now()->timestamp,
        );

        return response()->json([
            'idle_timeout_minutes' => SchoolSetting::current()->idle_timeout_minutes,
        ]);
    }
}
