<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EnsureTokenDeviceMatches
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->user()?->currentAccessToken();

        if (!$token) {
            return $next($request);
        }

        // Token name format: auth_token|{fingerprint_hash}
        $parts = explode('|', $token->name ?? '');

        // 🛡️ SECURITY FIX: Kung ang token walay fingerprint, i-reject dayon (Stolen attempt)
        if (count($parts) < 2) {
            return response()->json([
                'message' => 'Invalid security context. Access denied.'
            ], 403);
        }

        $storedHash = $parts[1]; // Ang hash gikan sa database

        // Mag-generate og fingerprint sa kasamtangang request
        $deviceHeader = $request->header('X-Device-Fingerprint');
        $deviceSource = $deviceHeader ?? ($request->userAgent() . '|' . $request->ip());
        $currentHash = hash('sha256', $deviceSource);

        // 🛡️ SECURITY FIX: I-compare ang fingerprint
        if (!hash_equals($storedHash, $currentHash)) {
            // Opsyonal: I-delete ang token sa database para dili na jud magamit sa kawatan
            // $token->delete(); 

            return response()->json([
                'message' => 'Security Alert: Device mismatch. This incident will be reported.'
            ], 401);
        }

        return $next($request);
    }
}