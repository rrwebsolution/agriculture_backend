<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Usba gikan sa Response ngadto sa JsonResponse
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $user = $request->user();

        // 🌟 I-load ang role relationship
        $user->load(['role']); 

        $expiresAt = now()->addHours((int) env('SANCTUM_TOKEN_EXPIRES_HOURS', 8));

        $deviceHeader = $request->header('X-Device-Fingerprint');
        $deviceSource = $deviceHeader ?? ($request->userAgent() . '|' . $request->ip());
        $fingerprintHash = hash('sha256', $deviceSource);

        $tokenName = 'auth_token|' . $fingerprintHash;
        $newToken = $user->createToken($tokenName, ['*'], $expiresAt);
        $token = $newToken->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, 
                'status' => $user->status,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}