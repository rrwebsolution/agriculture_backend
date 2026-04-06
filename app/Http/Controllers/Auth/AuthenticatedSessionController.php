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
        $user->load(['role']); 

        // Pag-set sa expiration (8 hours)
        $expiresAt = now()->addHours((int) env('SANCTUM_TOKEN_EXPIRES_HOURS', 8));

        // 🌟 FINGERPRINT LOGIC
        $deviceHeader = $request->header('X-Device-Fingerprint');
        // Siguroha nga ang middleware ug controller naggamit sa parehas nga source
        $deviceSource = $deviceHeader ?? ($request->userAgent() . '|' . $request->ip());
        $fingerprintHash = hash('sha256', $deviceSource);

        // I-save ang hash sa ngalan sa token
        $tokenName = 'auth_token|' . $fingerprintHash;
        
        // I-delete ang mga daan nga tokens para usa ra ka device ang active (Opsyonal)
        // $user->tokens()->delete(); 

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