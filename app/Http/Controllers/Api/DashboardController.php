<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Fisherfolk;
use App\Models\Harvest;
use App\Models\FisheryRecord;
use App\Models\Crop;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Get Totals
        $totalFarmers = Farmer::count();
        $totalFisherfolks = Fisherfolk::count();
        $totalCrops = Crop::count();
        
        // 2. Get Yields (Optional: based on your DB columns, assuming 'quantity' and 'yield')
        $farmingYield = Harvest::sum('quantity') ?? 0;
        $fisheryYield = FisheryRecord::sum('yield') ?? 0;

        // 3. Get Recent Activities (Mix of recent farmers & fisherfolks registrations)
        $recentFarmers = Farmer::with('barangay')->latest()->take(3)->get()->map(function($item) {
            return [
                'name' => $item->first_name . ' ' . $item->last_name,
                'loc' => $item->barangay->name ?? 'Unknown',
                'task' => 'Registered Farmer',
                'sector' => 'Farming',
                'time' => $item->created_at->diffForHumans()
            ];
        });

        $recentFisherfolks = Fisherfolk::with('barangay')->latest()->take(3)->get()->map(function($item) {
            return [
                'name' => $item->first_name . ' ' . $item->last_name,
                'loc' => $item->barangay->name ?? 'Unknown',
                'task' => 'Registered Fisherfolk',
                'sector' => 'Fishery',
                'time' => $item->created_at->diffForHumans()
            ];
        });

        // Merge and sort by newest
        $activities = collect($recentFarmers)->merge($recentFisherfolks)->sortByDesc('time')->values()->take(6);

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'farmers' => $totalFarmers,
                    'fisherfolks' => $totalFisherfolks,
                    'crops' => $totalCrops,
                    'farming_yield' => $farmingYield,
                    'fishery_yield' => $fisheryYield,
                ],
                'activities' => $activities
            ]
        ], 200);
    }
}