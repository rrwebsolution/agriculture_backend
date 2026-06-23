<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Rice Areas' => ['category' => 'Rice', 'crop_names' => 'Irrigated Rice, Rainfed Rice, Upland Rice'],
            'Corn Areas' => ['category' => 'Corn', 'crop_names' => 'Yellow Corn, White Corn, Sweet Corn'],
            'Root Crops Areas' => ['category' => 'Root Crops', 'crop_names' => 'Cassava, Sweet Potato, Taro, Yam'],
            'Vegetable Areas' => ['category' => 'Vegetable', 'crop_names' => 'Eggplant, Tomato, Squash, String Beans, Bitter Gourd, Pechay'],
            'Fruit Tree Areas' => ['category' => 'Fruit Tree', 'crop_names' => 'Mango, Mangosteen, Rambutan, Lanzones, Durian, Pineapple'],
            'Cacao Areas' => ['category' => 'Cacao', 'crop_names' => 'Cacao'],
            'Coffee Areas' => ['category' => 'Coffee', 'crop_names' => 'Arabica, Robusta, Liberica, Excelsa'],
            'Banana Areas' => ['category' => 'Banana', 'crop_names' => 'Lakatan, Cardava, Latundan, Saba'],
            'Coconut Areas' => ['category' => 'Coconut', 'crop_names' => 'Tall Coconut, Dwarf Coconut, Hybrid Coconut'],
            'Lawi Areas' => ['category' => 'Lawi', 'crop_names' => 'Lawi'],
            'Agroforestry Areas' => ['category' => 'Agroforestry', 'crop_names' => 'Cacao, Coffee, Coconut, Banana, Fruit Trees, Timber Trees'],
            'Idle/Brushland Areas' => ['category' => 'Idle/Brushland', 'crop_names' => 'Cogon, Talahib, Shrubs'],
            'Forest/Woodland Areas' => ['category' => 'Forest/Woodland', 'crop_names' => 'Narra, Mahogany, Falcata, Gmelina, Bamboo'],
        ];

        DB::transaction(function () use ($categories): void {
            foreach ($categories as $legacyCategory => $data) {
                $category = $data['category'];
                $legacyCrop = Crop::where('category', $legacyCategory)->first();
                $crop = Crop::where('category', $category)->first();

                if ($legacyCrop && ! $crop) {
                    $legacyCrop->update(['category' => $category]);
                    $crop = $legacyCrop;
                } elseif ($legacyCrop && $crop) {
                    DB::table('farmers')->where('crop_id', $legacyCrop->id)->update(['crop_id' => $crop->id]);
                    DB::table('plantings')->where('crop_id', $legacyCrop->id)->update(['crop_id' => $crop->id]);
                    DB::table('harvests')->where('crop_id', $legacyCrop->id)->update(['crop_id' => $crop->id]);
                    $legacyCrop->delete();
                }

                Crop::updateOrCreate(
                    ['category' => $category],
                    [
                        'crop_names' => $data['crop_names'],
                        'remarks' => 'Standard agricultural zone for '.$category.'.',
                    ]
                );
            }
        });
    }
}
