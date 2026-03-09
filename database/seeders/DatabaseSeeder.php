<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ✅ 1. Create Roles FIRST (so ID 1 exists)
            RoleSeeder::class, 

            // 2. Create Locations (Barangays)
            BarangaySeeder::class, 

            // 3. Create Crops
            CropSeeder::class,

            // ✅ 4. NOW create Users (because Role ID 1 now exists)
            UserSeeder::class,
        ]);
    }
}