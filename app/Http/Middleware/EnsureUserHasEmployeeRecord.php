<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The mobile kiosk only serves accounts linked to an employee record.
 */
class EnsureUserHasEmployeeRecord
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->employee !== null, 403, 'This account is not linked to an employee record.');

        return $next($request);
    }
}
