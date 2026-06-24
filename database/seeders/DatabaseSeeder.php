<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed data is only for bootstrapping local/test databases. The live
        // database is the source of truth and must never be synchronized back
        // to these sample/default records during a Forge deployment.
        if (app()->environment('production')) {
            $this->command?->warn(
                'Database seeding skipped: production data is preserved.'
            );

            return;
        }

        $this->call([
            RoleSeeder::class,      // Keep first (Users need Roles)
            BarangaySeeder::class, 
            CropSeeder::class,
            ClusterSeeder::class,   // <--- MOVE THIS UP (Users need Clusters)
            UserSeeder::class,      // <--- MOVE THIS DOWN
        ]);
    }
}
