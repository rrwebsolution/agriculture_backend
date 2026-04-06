<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role; // Import Role model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Find the ID dynamically
        $adminRole = Role::where('name', 'System Administrator')->first();

        User::create([
            'name' => 'Ryan Reyes',
            'email' => 'ryan@example.com',
            'password' => Hash::make('@password123'),
            'cluster_id' => 1, // Assuming Cluster ID 1 exists
            // Use the ID from the database, fallback to 1 if not found
            'role_id' => $adminRole ? $adminRole->id : 1, 
            'status' => 'active',
        ]);
    }
}