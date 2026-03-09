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
                    "Dashboard: View Overview Analytics","Dashboard: Export Daily Statistics",
                    "Farmer Registry: Manage Registered Farmers","Farmer Registry: Manage Cooperatives",
                    "Farmer Registry: Export Registry List","Production: Manage Crops","Production: Manage Planting Logs",
                    "Production: Manage Harvest Records","Livestock & Fish: Manage Fisheries","Livestock & Fish: Manage 
                    Livestock","Livestock & Fish: Manage Poultry","Resources: Manage Inventory","Resources: Manage Equipments",
                    "Resources: Manage Land Mapping","Finance: Manage Expenses","Finance: View Financial Reports",
                    "Finance: Delete Records","Access Control: Manage Roles","Access Control: Manage Users",
                    "Access Control: Assign Permissions","Audit Logs: View System Audit Logs","Audit Logs: Export Audit PDF",
                    "System Settings: Configure Global Settings","System Settings: Manage System Backups",
                    "Locations: Manage Barangay List","Locations: Manage Clusters"
                ]
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}