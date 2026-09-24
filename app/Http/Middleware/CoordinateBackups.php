<?php

namespace App\Http\Middleware;

use App\Services\BackupLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CoordinateBackups
{
    public function __construct(private BackupLock $lock) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $this->lock->run(
            fn (): Response => $next($request),
            exclusive: $request->routeIs('backups.store', 'backups.restore', 'backups.destroy'),
        );
    }
}
