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
        $coops = Cooperative::with('barangay')->latest()->get();
        
        $farmers = \App\Models\Farmer::with('crop')->whereNotNull('cooperative_id')->get();
        
        // 🌟 UPDATE: I-load ang catchRecords aron mo-reflect kini sa Cooperative Drawer/Dialog sa frontend
        $fisherfolks = \App\Models\Fisherfolk::with('catchRecords')->whereNotNull('cooperative_id')->get(); 
        
        $crops = \App\Models\Crop::pluck('category', 'id'); 

        $coops->transform(function ($coop) use ($farmers, $crops, $fisherfolks) {
            
            // --- 1. MAP FARMERS ---
            $assignedFarmers = $farmers->filter(function ($farmer) use ($coop) {
                $coopIds = (array) $farmer->cooperative_id;
                return in_array($coop->id, $coopIds) || in_array((string)$coop->id, $coopIds);
            })->map(function ($farmer) use ($crops) {
                $farmerData = $farmer->toArray();
                if (!empty($farmerData['farms_list']) && is_array($farmerData['farms_list'])) {
                    foreach ($farmerData['farms_list'] as &$farm) {
                        $cId = $farm['crop_id'] ?? null;
                        $farm['crop_name'] = $cId && isset($crops[$cId]) ? $crops[$cId] : 'Unknown Crop';
                    }
                }
                return $farmerData;
            });

            // --- 2. MAP FISHERFOLKS ---
            $assignedFisherfolks = $fisherfolks->filter(function ($fisher) use ($coop) {
                $coopIds = (array) $fisher->cooperative_id;
                return in_array($coop->id, $coopIds) || in_array((string)$coop->id, $coopIds);
            });

            $coop->assigned_farmers_count = $assignedFarmers->count(); 
            $coop->assigned_farmers_list = $assignedFarmers->values(); 
            
            $coop->assigned_fisherfolks_count = $assignedFisherfolks->count(); 
            $coop->assigned_fisherfolks_list = $assignedFisherfolks->values();

            return $coop;
        });

        return response()->json(['status' => 'success', 'data' => $coops]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id'    => 'required|unique:cooperatives,system_id',
            'cda_no'       => 'required|unique:cooperatives,cda_no',
            'name'         => 'required|string',
            'type'         => 'required|string',
            'registration' => 'required|string',
            'org_type'     => 'required|string',
            'chairman'     => 'required|string',
            'contact_no'   => 'nullable|string', 
            'barangay_id'  => 'required|exists:barangays,id',
            'address_details' => 'nullable|string', 
            'capital_cbu'  => 'required|numeric',
            'status'       => 'required|string',
        ]);

        $coop = Cooperative::create($validated);
        
        $this->broadcastBarangayUpdate($coop->barangay_id);
        event(new CooperativeUpdated($coop->load('barangay'), 'created'));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative registered successfully!',
            'data'    => $coop->load('barangay')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $coop = Cooperative::findOrFail($id);
        $old_brgy = $coop->barangay_id;

        $validated = $request->validate([
            'cda_no'       => 'required|unique:cooperatives,cda_no,' . $id,
            'name'         => 'required|string',
            'type'         => 'required|string',
            'registration' => 'required|string',
            'org_type'     => 'required|string',
            'chairman'     => 'required|string',
            'contact_no'   => 'nullable|string',
            'barangay_id'  => 'required|exists:barangays,id',
            'address_details' => 'nullable|string',
            'capital_cbu'  => 'required|numeric',
            'status'       => 'required|string',
        ]);

        $coop->update($validated);

        $this->broadcastBarangayUpdate($coop->barangay_id);
        if ($old_brgy && $old_brgy != $coop->barangay_id) {
            $this->broadcastBarangayUpdate($old_brgy);
        }
        
        event(new CooperativeUpdated($coop->load('barangay'), 'updated'));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative updated successfully!',
            'data'    => $coop->load('barangay')
        ]);
    }

    public function destroy($id)
    {
        $coop = Cooperative::findOrFail($id);
        $brgy_id = $coop->barangay_id;

        // CHECK KUNG NAA BAY FARMER
        $hasFarmers = \App\Models\Farmer::whereJsonContains('cooperative_id', (string)$coop->id)
                            ->orWhereJsonContains('cooperative_id', $coop->id)
                            ->exists();

        // CHECK KUNG NAA BAY FISHERFOLK
        $hasFisherfolks = \App\Models\Fisherfolk::whereJsonContains('cooperative_id', (string)$coop->id)
                            ->orWhereJsonContains('cooperative_id', $coop->id)
                            ->exists();

        if ($hasFarmers || $hasFisherfolks) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete. There are members assigned to this cooperative.'], 400);
        }

        $coop->delete();

        $this->broadcastBarangayUpdate($brgy_id);
        event(new CooperativeUpdated($coop, 'deleted'));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cooperative record deleted successfully.'
        ]);
    }
}