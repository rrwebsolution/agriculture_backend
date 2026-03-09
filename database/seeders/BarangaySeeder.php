<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $list = ["Agay-ayan", "Alagatan", "Anakan", "Bagubad", "Bakid-bakid", "Bal-ason", "Bantaawan", "Binakalan", 
        "Barangay 1", "Barangay 2", "Barangay 3", "Barangay 4", "Barangay 5", "Barangay 6", "Barangay 7", "Barangay 8", 
        "Barangay 9", "Barangay 10", "Barangay 11", "Barangay 12", "Barangay 13", "Barangay 14", "Barangay 15", 
        "Barangay 16", "Barangay 17", "Barangay 18", "Barangay 18-A", "Barangay 19", "Barangay 20", "Barangay 21", 
        "Barangay 22", "Barangay 22-A", "Barangay 23", "Barangay 24", "Barangay 24-A", "Barangay 25", "Barangay 26", 
        "Capitulangan", "Cotmon", "Daang-Lungsod", "Dinawehan", "Hindangon", "Kalagonoy", "Kalipay", "Kama-an", 
        "Kamanikan", "Kawayan", "Kibuging", "Kipuntos", "Lawaan", "Lawit", "Libertad", "Libon", "Lunao", "Lunotan", 
        "Maanas", "Malibud", "Malinao", "Maribucao", "Mimbama", "Mimbalagon", "Mimbuahan", "Minsapinit", "Murallon", 
        "Odiongan", "Pangasihan", "Pigsaluhan", "Punong", "Ricorro", "Samay", "San Juan", "San Luis", "San Miguel", 
        "San Roque", "Sangalan", "Santiago", "Talisay", "Talon", "Tinulongan"];

        foreach ($list as $index => $name) {
            $type = "Rural";
            if (str_contains($name, "Barangay")) $type = "Urban (Poblacion)";
            if (in_array($name, ["Anakan", "Odiongan", "Lunao", "Agay-ayan", "Punong"])) $type = "Coastal";

            $total = rand(100, 500);
            \App\Models\Barangay::create([
                'name' => $name,
                'code' => 'BRGY-' . (1001 + $index),
                'type' => $type,
            ]);
        }
    }
}
