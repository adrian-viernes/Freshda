<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response headers on every page and endpoint: clickjacking
 * protection, no MIME sniffing and no cross-origin referrer leakage. A
 * Content-Security-Policy is deliberately left out — the dashboard relies on
 * inline scripts and CDN assets, which a strict CSP would break.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');

        return $response;
    }
}
