<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\Barangay; 
use Illuminate\Http\Request;
use App\Events\CooperativeUpdated;
use App\Events\BarangayUpdated;

class CooperativeController extends Controller
{
    private function broadcastBarangayUpdate($barangay_id) {
        if (!$barangay_id) return;
        $b = Barangay::with(['farmers', 'fisherfolks', 'cooperatives'])->findOrFail($barangay_id);
        event(new BarangayUpdated([
            'id' => $b->id,
            'name' => $b->name,
            'code' => $b->code,
            'type' => $b->type,
            'farmers' => $b->farmers->count(),
            'fisherfolks' => $b->fisherfolks->count(),
            'cooperatives_count' => $b->cooperatives->count(),
            'farmersList' => $b->farmers,
            'fisherfolksList' => $b->fisherfolks,
            'cooperativesList' => $b->cooperatives
        ], 'updated'));
    }

    public function index()
    {
        $coops = Cooperative::with('barangay','farmers')->latest()->get();
        return response()->json(['status' => 'success', 'data' => $coops]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id'    => 'required|unique:cooperatives,system_id',
            'cda_no'       => 'required|unique:cooperatives,cda_no',
            'name'         => 'required|string',
            'type'         => 'required|string',
            'chairman'     => 'required|string',
            'contact_no'   => 'nullable|string', 
            'barangay_id'  => 'required|exists:barangays,id',
            'address_details' => 'nullable|string', 
            'member_count' => 'required|integer',
            'capital_cbu'  => 'required|numeric',
            'status'       => 'required|string',
        ]);

        $coop = Cooperative::create($validated);
        
        // 🌟 I-broadcast update sa barangay
        $this->broadcastBarangayUpdate($coop->barangay_id);
        
        event(new CooperativeUpdated($coop->load('barangay', 'farmers'), 'created'));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative registered successfully!',
            'data'    => $coop->load('barangay')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $coop = Cooperative::findOrFail($id);
        
        // 🌟 I-save ang karaan nga barangay ID para sa comparison
        $old_brgy = $coop->barangay_id;

        $validated = $request->validate([
            'cda_no'       => 'required|unique:cooperatives,cda_no,' . $id,
            'name'         => 'required|string',
            'type'         => 'required|string',
            'chairman'     => 'required|string',
            'contact_no'   => 'nullable|string',
            'barangay_id'  => 'required|exists:barangays,id',
            'address_details' => 'nullable|string',
            'member_count' => 'required|integer',
            'capital_cbu'  => 'required|numeric',
            'status'       => 'required|string',
        ]);

        $coop->update($validated);

        // 🌟 I-update ang bag-ong barangay
        $this->broadcastBarangayUpdate($coop->barangay_id);
        
        // 🌟 I-update ang karaan nga barangay kung gi-usab ang location
        if ($old_brgy && $old_brgy != $coop->barangay_id) {
            $this->broadcastBarangayUpdate($old_brgy);
        }
        
        event(new CooperativeUpdated($coop->load('barangay', 'farmers'), 'updated'));
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative updated successfully!',
            'data'    => $coop->load('barangay')
        ]);
    }

    public function destroy($id)
    {
        $coop = Cooperative::findOrFail($id);
        
        // 🌟 I-save ang barangay ID usa i-delete
        $brgy_id = $coop->barangay_id;

        // Validation checks
        if ($coop->farmers()->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete...'], 400);
        }

        $coop->delete();

        // 🌟 I-update ang barangay metrics
        $this->broadcastBarangayUpdate($brgy_id);
        
        event(new CooperativeUpdated($coop, 'deleted'));
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative record deleted successfully.'
        ]);
    }
}