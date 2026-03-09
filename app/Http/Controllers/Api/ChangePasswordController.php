<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordController extends Controller
{
    public function updatePassword(Request $request)
    {
        // Debugging: Tan-awa kung unsa gyud ang sulod sa $request->user()
        // dd($request->user()); 

        // Siguroha nga ang user tinuod nga na-login
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated user.'], 401);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check kung sakto ba ang current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match.'], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password updated!']);
    }
}
