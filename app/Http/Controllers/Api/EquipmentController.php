<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Cooperative;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the equipments.
     */
    public function index(Request $request)
    {
        $query = Equipment::with(['cooperatives.barangay']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->type && $request->type !== 'All Types') {
            $query->where('type', $request->type);
        }

        $equipments = $query->latest()->get();

        $formatted = $equipments->map(function ($equipment) {
            return $this->formatEquipment($equipment);
        });

        return response()->json($formatted);
    }

    /**
     * Store a newly created equipment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|unique:equipments,sku',
            'name' => 'required|string',
            'type' => 'required|string',
            'program' => 'nullable|string',
            'condition' => 'required|string',
            'status' => 'required|string',
            'lastCheck' => 'nullable|date',
            'beneficiary' => 'nullable|array' 
        ]);

        // Create the equipment (mapping frontend lastCheck to DB last_check)
        $equipment = Equipment::create([
            'sku'        => $validated['sku'],
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'program'    => $validated['program'],
            'condition'  => $validated['condition'],
            'status'     => $validated['status'],
            'last_check' => $request->lastCheck,
        ]);

        // Sync Cooperatives if beneficiaries are provided
        if ($request->has('beneficiary') && !empty($request->beneficiary)) {
            $ids = Cooperative::whereIn('name', $request->beneficiary)->pluck('id');
            $equipment->cooperatives()->sync($ids);
        }

        // Return the formatted object for the frontend Redux state
        $equipment->load(['cooperatives.barangay']);
        return response()->json($this->formatEquipment($equipment), 201);
    }

    /**
     * Update the specified equipment.
     */
    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        
        $validated = $request->validate([
            'sku' => 'required|unique:equipments,sku,' . $id,
            'name' => 'required|string',
            'type' => 'required|string',
            'program' => 'nullable|string',
            'condition' => 'required|string',
            'status' => 'required|string',
            'lastCheck' => 'nullable|date',
            'beneficiary' => 'nullable|array'
        ]);

        $equipment->update([
            'sku'        => $validated['sku'],
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'program'    => $validated['program'],
            'condition'  => $validated['condition'],
            'status'     => $validated['status'],
            'last_check' => $request->lastCheck, 
        ]);

        if ($request->has('beneficiary')) {
            $ids = Cooperative::whereIn('name', $request->beneficiary)->pluck('id');
            $equipment->cooperatives()->sync($ids);
        }

        $equipment->load(['cooperatives.barangay']);

        return response()->json($this->formatEquipment($equipment));
    }

    /**
     * Remove the specified equipment.
     */
    public function destroy($id)
    {
        Equipment::destroy($id);
        return response()->json(['message' => 'Equipment removed successfully']);
    }

    /**
     * Fetch lookups for modals.
     */
    public function lookups()
    {
        return response()->json([
            'cooperatives' => Cooperative::with('barangay')->get(),
            'barangays' => Barangay::select('id', 'name')->get()
        ]);
    }

    /**
     * Helper method to format the equipment object for the frontend.
     */
    private function formatEquipment($equipment)
    {
        return [
            'id' => $equipment->id,
            'sku' => $equipment->sku,
            'name' => $equipment->name,
            'type' => $equipment->type,
            'program' => $equipment->program,
            'condition' => $equipment->condition,
            'status' => $equipment->status,
            // Format date for display
            'lastCheck' => $equipment->last_check ? Carbon::parse($equipment->last_check)->format('M d, Y') : 'N/A',
            // Pluck names for the "beneficiary" tags
            'beneficiary' => $equipment->cooperatives->pluck('name')->toArray(),
            // Map through coops to get unique barangay names
            'location' => $equipment->cooperatives->map(function($c) {
                return $c->barangay ? $c->barangay->name : null;
            })->filter()->unique()->values()->toArray(),
        ];
    }
}