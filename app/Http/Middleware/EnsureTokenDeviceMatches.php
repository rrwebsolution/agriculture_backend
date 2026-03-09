<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenDeviceMatches
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return $next($request);
        }

        // Token name format: {name}|{fingerprint_hash}
        $parts = explode('|', $token->name ?? '');

        if (count($parts) < 2) {
            // No fingerprint bound to token: allow (for backward compatibility)
            return $next($request);
        }

        $storedHash = $parts[count($parts) - 1];

        $deviceHeader = $request->header('X-Device-Fingerprint');
        $deviceSource = $deviceHeader ?? ($request->userAgent() . '|' . $request->ip());
        $currentHash = hash('sha256', $deviceSource);

        if (! hash_equals($storedHash, $currentHash)) {
            return response()->json([
                'message' => 'Token device mismatch'
            ], 401);
        }

        return $next($request);
    }
}
