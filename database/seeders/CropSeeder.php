<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Crop;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Rice Areas', 
            'Corn Areas', 
            'Root Crops Areas', 
            'Vegetable Areas', 
            'Fruit Tree Areas', 
            'Cacao Areas', 
            'Coffee Areas', 
            'Banana Areas', 
            'Coconut Areas', 
            'Lawi Areas', 
            'Agroforestry Areas', 
            'Idle/Brushland Areas', 
            'Forest/Woodland Areas'
        ];

        foreach ($categories as $category) {
            Crop::create([
                'category' => $category,
                // Farmers field removed (calculated dynamically via relationship)
                'remarks' => 'Standard agricultural zone for ' . $category . '.'
            ]);
        }
    }
}