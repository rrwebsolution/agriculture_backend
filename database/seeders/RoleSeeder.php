<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'System Administrator',
                'description' => 'Full system access, managing users, roles, and global configurations.',
                'permissions' => [
                    // Dashboard
                    "Dashboard: View Overview Analytics",
                    
                    // Farmer Registry
                    "Farmer Registry: View Registered Farmers",
                    "Farmer Registry: Manage Registered Farmers",
                    
                    // Fisherfolk Registry
                    "Fisherfolk Registry: View Registered Fisherfolks",
                    "Fisherfolk Registry: Manage Registered Fisherfolks",
                    
                    // Cooperatives
                    "Cooperatives: View Cooperatives",
                    "Cooperatives: Manage Cooperatives",
                    
                    // Locations
                    "Locations: View Barangay List",
                    "Locations: Manage Barangay List",
                    "Locations: View Clusters",
                    "Locations: Manage Clusters",
                    
                    // Production (Crop Agriculture)
                    "Production: View Crops",
                    "Production: Manage Crops",
                    "Production: View Planting Logs",
                    "Production: Manage Planting Logs",
                    "Production: View Harvest Records",
                    "Production: Manage Harvest Records",
                    
                    // Fishery
                    "Fishery: View Fisheries",
                    "Fishery: Manage Fisheries",
                    
                    // Resources
                    "Resources: View Inventory",
                    "Resources: Manage Inventory",
                    "Resources: View Equipments",
                    "Resources: Manage Equipments",
                    "Resources: View Land Mapping",
                    "Resources: Manage Land Mapping",
                    
                    // Finance
                    "Finance: View Expenses",
                    "Finance: Manage Expenses",
                    "Finance: View Financial Reports",
                    
                    // Access Control
                    "Access Control: View Roles",
                    "Access Control: Manage Roles",
                    "Access Control: View Users",
                    "Access Control: Manage Users",
                    
                    // Audit Logs
                    "Audit Logs: View System Audit Logs",
                    
                    // System Settings
                    "System Settings: View Global Settings",
                    "System Settings: Configure Global Settings"
                ]
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']], // Check if role exists by name
                ['description' => $role['description'], 'permissions' => $role['permissions']]
            );
        }
    }
}