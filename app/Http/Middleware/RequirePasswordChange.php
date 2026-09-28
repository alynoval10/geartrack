<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password
            && ! $request->routeIs(
                'filament.admin.pages.change-password',
                'password.update',
                'filament.admin.auth.logout',
                'session.activity',
            )) {
            return redirect()->route('filament.admin.pages.change-password');
        }

        return $next($request);
    }
}
