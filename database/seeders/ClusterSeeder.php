<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cluster; // Import the Cluster model
use Illuminate\Support\Facades\DB;

class ClusterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Option 1: Using the Model (Recommended)
        Cluster::updateOrCreate(
            ['name' => 'Main Office'], // This prevents duplicates if you run it twice
            [
                'description' => 'Central Administrative Office for LGU Gingoog Agriculture.',
                'status' => 'Active',
            ]
        );
    }
}