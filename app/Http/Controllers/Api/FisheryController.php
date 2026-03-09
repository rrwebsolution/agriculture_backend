<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fishery;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FisheryController extends Controller
{
    public function index()
    {
        // Removed 'fisherfolk' relationship eager load since ID is removed
        $data = Fishery::with(['location'])->orderBy('date', 'desc')->get();
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // 🌟 REMOVED: fisherfolk_id
            'boat_name'     => 'nullable|string',
            'gear_type'     => 'required|string',
            'location_id'   => 'required|exists:barangays,id',
            'catch_species' => 'required|string',
            'yield'         => 'required|numeric',
            'date'          => 'required|date',
        ]);

        // 🌟 Auto-generate FishR ID (Backend Only)
        $validated['fishr_id'] = 'FSH-' . date('Y') . '-' . strtoupper(Str::random(5));

        $fishery = Fishery::create($validated);
        $fishery->load(['location']);

        return response()->json([
            'message' => 'Catch record saved successfully',
            'data' => $fishery
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fishery = Fishery::findOrFail($id);

        $validated = $request->validate([
            // 🌟 REMOVED: fisherfolk_id
            'boat_name'     => 'nullable|string',
            'gear_type'     => 'required|string',
            'location_id'   => 'required|exists:barangays,id',
            'catch_species' => 'required|string',
            'yield'         => 'required|numeric',
            'date'          => 'required|date',
        ]);

        $fishery->update($validated);
        $fishery->load(['location']);

        return response()->json([
            'message' => 'Record updated successfully',
            'data' => $fishery
        ]);
    }

    public function destroy($id)
    {
        $fishery = Fishery::findOrFail($id);
        $fishery->delete();
        return response()->json(['message' => 'Record deleted successfully']);
    }
}