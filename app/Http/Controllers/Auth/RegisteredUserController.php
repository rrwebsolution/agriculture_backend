<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    // Usba ang return type diri ngadto sa JsonResponse
    public function store(Request $request): JsonResponse 
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['nullable', 'exists:roles,id'], 
            'cluster_id' => ['nullable', 'exists:clusters,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'role_id' => $request->role_id,
            'cluster_id' => $request->cluster_id,
            'status' => 'inactive',
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Karon, kining response()->json() match na sa imong JsonResponse type hint
        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }
}