<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            Log::channel('security')->warning('Non-admin blocked from admin area', [
                'user_id' => $request->user()?->getAuthIdentifier(),
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            abort(403, 'Administrator access required.');
        }

        return $next($request);
    }
}
