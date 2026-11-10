<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defensive HTTP response headers to every web and API response.
 *
 * The headers are intentionally conservative and framework-friendly: they do
 * not restrict scripts or styles (which would break Inertia/Vite) but they do
 * block framing, MIME sniffing, referrer leakage and cross-origin windows.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Content-Security-Policy' => "frame-ancestors 'none'; base-uri 'self'; object-src 'none'",
        ];

        // HSTS is only meaningful (and only safe) over HTTPS.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Authenticated pages and API payloads must never be cached by shared
        // proxies or the browser back/forward cache.
        if (! $response->headers->has('Cache-Control')
            && ($request->user() !== null || $request->is('api/*'))) {
            $response->headers->set(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            );
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
