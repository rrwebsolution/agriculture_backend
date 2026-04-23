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
        $adminRole = Role::where('name', 'System Administrator')->first();

        User::updateOrCreate([
            'email' => 'ryan@example.com',
        ], [
            'name' => 'Ryan Reyes',
            'password' => Hash::make('@password123'),
            'cluster_id' => 1,
            'role_id' => $adminRole ? $adminRole->id : 1,
            'status' => 'active',
        ]);
    }
}
