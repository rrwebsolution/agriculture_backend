<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenNotExpired
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
{
    $user = $request->user();
    $token = $user?->currentAccessToken();

    if (!$token) {
        return $next($request);
    }

    $parts = explode('|', $token->name ?? '');

    // 🌟 STALKER PROTECTION: Rehektahon kung walay fingerprint bound sa token
    if (count($parts) < 2) {
        return response()->json(['message' => 'Invalid token structure'], 401);
    }

    $storedHash = $parts[count($parts) - 1];

    // Mag-create og fingerprint base sa attacker's environment
    $deviceHeader = $request->header('X-Device-Fingerprint');
    $deviceSource = $deviceHeader ?? ($request->userAgent() . '|' . $request->ip());
    $currentHash = hash('sha256', $deviceSource);

    // 🌟 KINI ANG MO-BLOCK SA STOLEN TOKEN
    if (!hash_equals($storedHash, $currentHash)) {
        // Opsyonal: I-delete ang token para dili na magamit pag-usab sa attacker
        // $token->delete(); 

        return response()->json([
            'message' => 'Security Alert: Device mismatch. Access denied.'
        ], 401);
    }

    return $next($request);
}
}
