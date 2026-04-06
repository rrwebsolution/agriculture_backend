<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,      // Keep first (Users need Roles)
            BarangaySeeder::class, 
            CropSeeder::class,
            ClusterSeeder::class,   // <--- MOVE THIS UP (Users need Clusters)
            UserSeeder::class,      // <--- MOVE THIS DOWN
        ]);
    }
}