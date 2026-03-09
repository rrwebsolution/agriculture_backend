<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // I-fetch ang roles kauban ang ihap sa users nga naka-assign niini
        $roles = Role::withCount('users')->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'required|array' // Ang array gikan sa React
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => $request->permissions, // I-save diretso as JSON array
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => $role
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 1. Pangitaon ang role base sa ID
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // 2. I-validate ang data (siguroha nga ang 'unique' mo-exclude sa current ID)
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'required|array'
        ]);

        try {
            // 3. I-update ang data
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => $request->permissions,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $role
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // (Optional) Para sa Delete:
    public function destroy($id)
    {
        // 1. Pangitaon ang role kauban ang ihap sa users
        $role = Role::withCount('users')->find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // 2. CHECK: Kung ang users_count labaw sa 0, dili pwede i-delete
        if ($role->users_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Forbidden: This role has {$role->users_count} assigned users. Reassign them first before deleting."
            ], 422); // 422 Unprocessable Entity
        }

        try {
            $role->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Role has been successfully removed.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error during deletion'], 500);
        }
    }

}
