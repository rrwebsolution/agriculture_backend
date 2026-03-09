<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Helper function para uniform ang format sa User Data
     */
    private function formatUser($user)
{
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role, // Object containing name
        'cluster' => $user->cluster, // Object containing name
        'status' => $user->status ?? 'Active',
    ];
}

    public function getUserData()
{
    $users = User::with(['role', 'cluster'])->latest()->get();
    $formatted = $users->map(fn($u) => $this->formatUser($u));
    return response()->json(['status' => 'success', 'data' => $formatted]);
}

    public function store(Request $request)
    {
         $validator = Validator::make($request->all(), [
        'name'   => 'required|string|max:255',
        'email'  => 'required|email|unique:users,email',
        'role'   => 'required|exists:roles,id', 
        'cluster_id' => 'nullable|exists:clusters,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(), 
            'errors' => $validator->errors()
        ], 422);
    }

        $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'role_id' => $request->role,
        'cluster_id' => $request->cluster,
        'password' => Hash::make('Gingoog@2026'),
        'status' => 'active',
    ]);

    return response()->json([
        'status' => 'success',
        'data' => $this->formatUser($user->load(['role', 'cluster']))
    ]);
    }

    // Update User
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // 🌟 Gihimo natong 'nullable' ang role ug cluster para maski usa ra ang i-update, mo-submit gihapon.
        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:255',
            'email'  => 'sometimes|required|email|unique:users,email,' . $id,
            'role'   => 'sometimes|nullable|exists:roles,id',   // 👈 Gihimong nullable
            'cluster' => 'sometimes|nullable|exists:clusters,id', // 👈 Gihimong nullable
            'status' => 'sometimes|required|in:active,inactive', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => $validator->errors()->first()
            ], 422);
        }

        $dataToUpdate = [];

        // I-check kung unsa ra nga data ang naa sa request (Partial Update)
        if ($request->has('name'))   $dataToUpdate['name'] = $request->name;
        if ($request->has('email'))  $dataToUpdate['email'] = $request->email;
        if ($request->has('status')) $dataToUpdate['status'] = $request->status;
        
        // 🌟 Kini nga logic mo-allow sa pag-update sa role_id maski walay cluster_id sa request
        if ($request->has('role'))   $dataToUpdate['role_id'] = $request->role;
        if ($request->has('cluster')) $dataToUpdate['cluster_id'] = $request->cluster;

        // I-execute ang update sa mga fields lang nga nausab
        $user->update($dataToUpdate);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully!',
            'data' => $this->formatUser($user->load(['role', 'cluster']))
        ]);
    }

    // Delete User
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['status' => 'success', 'message' => 'User deleted successfully!']);
    }

    public function me(Request $request)
    {
        // Kuhaon ang logged-in user uban ang iyang role ug permissions
        $user = $request->user()->load('role');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, // Naa na diri ang permissions array
                'status' => $user->status,
            ]
        ]);
    }
}