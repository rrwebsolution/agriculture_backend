<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barangay;
use Illuminate\Support\Facades\DB;

class BarangaySeeder extends Seeder
{
    public function run()
    {
        // 🌟 Ang Kumpleto ug Eksakto nga 79 ka Barangay sa Gingoog City
        $list = [
            "Agay-ayan", "Alagatan", "Anakan", "Bagubad", "Bakid-bakid", "Bal-ason", "Bantaawan", "Binakalan", 
            "Barangay 1", "Barangay 2", "Barangay 3", "Barangay 4", "Barangay 5", "Barangay 6", "Barangay 7", 
            "Barangay 8", "Barangay 9", "Barangay 10", "Barangay 11", "Barangay 12", "Barangay 13", "Barangay 14", 
            "Barangay 15", "Barangay 16", "Barangay 17", "Barangay 18", "Barangay 18-A", "Barangay 19", "Barangay 20", 
            "Barangay 21", "Barangay 22", "Barangay 22-A", "Barangay 23", "Barangay 24", "Barangay 24-A", "Barangay 25", 
            "Barangay 26", "Capitulangan", "Cotmon", "Daang-Lungsod", "Dinawehan", "Hindangon", "Kalagonoy", "Kalipay", 
            "Kama-an", "Kamanikan", "Kawayan", "Kibuging", "Kipuntos", "Lawaan", "Lawit", "Libertad", "Libon", 
            "Lunao", "Lunotan", "Maanas", "Malibud", "Malinao", "Maribucao", "Mimbama", "Mimbalagon", "Mimbuahan", 
            "Minsapinit", "Murallon", "Odiongan", "Pangasihan", "Pigsaluhan", "Punong", "Ricorro", "Samay", 
            "San Juan", "San Luis", "San Miguel", "San Roque", "Sangalan", "Santiago", "Talisay", "Talon", "Tinulongan"
        ];

        // Gingoog City Base Coordinates
        $baseLat = 8.8234;
        $baseLng = 125.1234;

        foreach ($list as $index => $name) {
            $type = "Rural";
            
            if (str_contains($name, "Barangay")) {
                $type = "Urban (Poblacion)";
            }
            
            if (in_array($name, ["Anakan", "Odiongan", "Lunao", "Agay-ayan", "Punong", "San Juan", "Talisay", "Daang-Lungsod", "Pangasihan", "San Roque", "Bantaawan"])) {
                $type = "Coastal";
            }

            // 🌟 MAG-GENERATE UG RANDOM COORDINATES PALIBOT SA GINGOOG PARA SA MGA WALAY DATA
            $radius = 0.05; // radius spread
            $angle = ($index / count($list)) * M_PI * 2;
            $lat = $baseLat + cos($angle) * $radius * (mt_rand(50, 100) / 100);
            $lng = $baseLng + sin($angle) * $radius * (mt_rand(50, 100) / 100);

            // GIGAMIT NATO ANG updateOrCreate ARON MA-UPDATE RA ANG COORDINATES, WALAY MA DELETE NGA FARMERS O CROPS
            Barangay::updateOrCreate(
                ['name' => $name], // Pangitaon ang barangay gamit ang ngalan
                [
                    'code' => 'BRGY-' . (1001 + $index),
                    'type' => $type,
                    'latitude' => round($lat, 6),
                    'longitude' => round($lng, 6),
                ]
            );
        }
    }
}