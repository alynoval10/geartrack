<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleSession
{
    public const LAST_ACTIVITY_KEY = 'geartrack.last_activity_at';

    public const USER_VERSION_KEY = 'geartrack.user_session_version';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $session = $request->session();
        $lastActivity = $session->get(self::LAST_ACTIVITY_KEY);
        $sessionVersion = $session->get(self::USER_VERSION_KEY);
        $idleSeconds = max(1, (int) config('session.idle_timeout', 5)) * 60;

        $isIdle = is_numeric($lastActivity)
            && (now()->timestamp - (int) $lastActivity) >= $idleSeconds;
        $isRevoked = $sessionVersion !== null
            && (int) $sessionVersion !== (int) $user->session_version;

        if ($isIdle || $isRevoked) {
            // Invalidasi penuh mencegah ID sesi lama digunakan kembali setelah logout.
            Auth::guard()->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('session_status', $isIdle
                    ? 'Sesi berakhir karena tidak ada aktivitas selama 5 menit.'
                    : 'Sesi berakhir karena akun Anda diperbarui oleh administrator.');
        }

        $session->put([
            self::LAST_ACTIVITY_KEY => now()->timestamp,
            self::USER_VERSION_KEY => (int) $user->session_version,
        ]);

        return $next($request);
    }
}
