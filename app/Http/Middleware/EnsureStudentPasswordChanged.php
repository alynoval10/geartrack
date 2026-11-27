<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user('student');

        if ($student?->must_change_password
            && ! in_array($request->route()?->getName(), ['student.password.edit', 'student.password.update'], true)) {
            return redirect()->route('student.password.edit');
        }

        return $next($request);
    }
}
