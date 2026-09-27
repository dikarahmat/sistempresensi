<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Tambahkan security headers untuk proteksi dasar:
     * - X-Frame-Options: mencegah clickjacking
     * - X-Content-Type-Options: mencegah MIME sniffing
     * - X-XSS-Protection: filter XSS lama
     * - Referrer-Policy: kontrol referer
     * - CSP: Content Security Policy
     * - HSTS: HTTPS only (hanya di production)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Mencegah clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Mencegah MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS protection legacy
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(self)');

        // Content Security Policy (longgar untuk development, bisa diperketat)
        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://code.jquery.com; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://unpkg.com; "
            . "img-src 'self' data: blob: https:; "
            . "connect-src 'self' https:; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'; "
            . "form-action 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS hanya di production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
