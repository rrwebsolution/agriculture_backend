<?php

namespace App\Http\Controllers\Api;

use App\Events\HarvestUpdated;
use App\Http\Controllers\Controller;
use App\Models\Harvest;
use Illuminate\Http\Request;

class HarvestController extends Controller
{
    public function index()
    {
        // 🌟 UPDATED: Load 'barangay' instead of 'cluster'
        $harvests = Harvest::with(['farmer', 'barangay', 'crop'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['data' => $harvests], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'barangay_id' => 'required|exists:barangays,id', // 🌟 UPDATED
            'crop_id' => 'required|exists:crops,id',
            'dateHarvested' => 'required|date',
            'quantity' => 'required|string',
            'quality' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $harvest = Harvest::create($validated);
        
        // 🌟 UPDATED: Load 'barangay'
        $harvest->load(['farmer', 'barangay', 'crop']); 
        event(new HarvestUpdated($harvest, 'created'));

        return response()->json([
            'message' => 'Harvest recorded successfully.',
            'data' => $harvest
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $harvest = Harvest::findOrFail($id);

        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'barangay_id' => 'required|exists:barangays,id', // 🌟 UPDATED
            'crop_id' => 'required|exists:crops,id',
            'dateHarvested' => 'required|date',
            'quantity' => 'required|string',
            'quality' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $harvest->update($validated);
        
        // 🌟 UPDATED: Load 'barangay'
        $harvest->load(['farmer', 'barangay', 'crop']); 
        event(new HarvestUpdated($harvest, 'updated'));

        return response()->json([
            'message' => 'Harvest updated successfully.',
            'data' => $harvest
        ], 200);
    }

    public function destroy($id)
    {
        $harvest = Harvest::findOrFail($id);
        $harvest->load(['farmer', 'barangay', 'crop']);
        $harvest->delete();
        event(new HarvestUpdated($harvest, 'deleted'));

        return response()->json(['message' => 'Harvest deleted successfully.'], 200);
    }
}
