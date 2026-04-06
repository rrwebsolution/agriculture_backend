<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planting;
use App\Models\PlantingStatusHistory;
use App\Events\PlantingUpdated; // 🌟 Gi-import ang Event para sa Real-time

class PlantingController extends Controller
{
    public function index()
    {
        // Fetch lang sa plantings diin ang Farmer kay 'active'
        $plantings = Planting::whereHas('farmer', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['farmer', 'barangay', 'crop', 'statusHistory'])
            ->latest()
            ->get();
        
        return response()->json([
            'success' => true,
            'count'   => $plantings->count(),
            'data'    => $plantings
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farmer_id'    => 'required|exists:farmers,id',
            'barangay_id'  => 'required|exists:barangays,id', 
            'crop_id'      => 'required|exists:crops,id',
            'area'         => 'required|numeric|min:0.01',
            'date_planted' => 'required|date',
            'est_harvest'  => 'required|date|after_or_equal:date_planted',
            'status'       => 'required|string|max:50',
        ]);

        $planting = Planting::create($validated);

        // CREATE INITIAL HISTORY LOG
        $planting->statusHistory()->create([
            'status' => $planting->status,
            'remarks' => "Initial log: Crop was planted as " . $planting->status . "."
        ]);
        
        $planting->load(['farmer', 'barangay', 'crop', 'statusHistory']);

        // 🌟 REAL-TIME BROADCAST: Created
        event(new PlantingUpdated($planting, 'created'));

        return response()->json([
            'success' => true,
            'message' => 'Planting log successfully saved!',
            'data'    => $planting
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $planting = Planting::findOrFail($id);

        $validated = $request->validate([
            'farmer_id'    => 'required|exists:farmers,id',
            'barangay_id'  => 'required|exists:barangays,id', 
            'crop_id'      => 'required|exists:crops,id',
            'area'         => 'required|numeric|min:0.01',
            'date_planted' => 'required|date',
            'est_harvest'  => 'required|date|after_or_equal:date_planted',
            'status'       => 'required|string|max:50',
        ]);

        $statusChanged = $planting->status !== $validated['status'];
        $planting->update($validated);

        if ($statusChanged) {
            $remarks = "System logged growth progress: Crop has reached the " . $validated['status'] . " stage.";
            
            if (strtolower($validated['status']) === 'harvested') {
                $remarks = "Success: Crop has been successfully harvested!";
            } elseif (in_array(strtolower($validated['status']), ['destroyed', 'damaged'])) {
                $remarks = "Alert: Crop was logged as destroyed or damaged.";
            }

            $planting->statusHistory()->create([
                'status' => $validated['status'],
                'remarks' => $remarks
            ]);
        }

        $planting->load(['farmer', 'barangay', 'crop', 'statusHistory']);

        // 🌟 REAL-TIME BROADCAST: Updated
        event(new PlantingUpdated($planting, 'updated'));

        return response()->json([
            'success' => true,
            'message' => 'Planting log updated successfully!',
            'data'    => $planting
        ], 200);
    }

    public function destroy($id)
    {
        $planting = Planting::findOrFail($id);
        
        // I-save ang ID sa dili pa i-delete para mapadala sa websocket
        $deletedId = $planting->id;

        if ($planting->statusHistory()->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this record because it contains active growth history data. Delete the history logs first.'
            ], 403);
        }

        $planting->delete();

        // 🌟 REAL-TIME BROADCAST: Deleted
        // Magpadala lang ta og object nga naay ID para mahibalo ang frontend unsa ang tangtangon
        event(new PlantingUpdated(['id' => $deletedId], 'deleted'));

        return response()->json([
            'success' => true,
            'message' => 'Planting log deleted successfully!'
        ], 200);
    }

    public function destroyHistory($id)
    {
        $history = PlantingStatusHistory::findOrFail($id);
        $planting = $history->planting;

        if ($planting->statusHistory()->count() <= 1) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete the only remaining history record.'
            ], 400);
        }

        $history->delete();

        // Rollback the main Planting status
        $latestHistory = $planting->statusHistory()->latest()->first();
        if ($latestHistory) {
            $planting->update(['status' => $latestHistory->status]);
        }

        $planting->load(['farmer', 'barangay', 'crop', 'statusHistory']);

        // 🌟 REAL-TIME BROADCAST: Updated (kay nausab ang status sa main record)
        event(new PlantingUpdated($planting, 'updated'));

        return response()->json([
            'success' => true,
            'message' => 'History record deleted successfully!',
            'data'    => $planting
        ], 200);
    }
}