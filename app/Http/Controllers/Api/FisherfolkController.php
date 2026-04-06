<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fisherfolk;
use Illuminate\Http\Request;
use App\Events\FisherfolkUpdated;
use App\Models\Barangay; 
use App\Events\BarangayUpdated; 

class FisherfolkController extends Controller
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
        $records = Fisherfolk::with(['barangay', 'catchRecords'])->latest()->get();
        return response()->json(['status' => 'success', 'data' => $records]);
    }

    public function store(Request $request)
    {
        // 🌟 GI-UPDATE ANG VALIDATION PARA MO-MATCH SA FRONTEND REQUIRED FIELDS & ARRAYS
        $validated = $request->validate([
            'system_id'          => 'required|unique:fisherfolks,system_id',
            'first_name'         => 'required|string',
            'last_name'          => 'required|string',
            'gender'             => 'required|string',
            'dob'                => 'required|date',
            'civil_status'       => 'required|string',
            'barangay_id'        => 'required|exists:barangays,id',
            'address_details'    => 'required|string',
            
            'fisher_type'        => 'required|string',
            'years_in_fishing'   => 'required|numeric',
            
            // 🌟 Arrays
            'cooperative_id'     => 'nullable|array',
            'boats_list'         => 'nullable|array',
            'assistances_list'   => 'nullable|array',

            // 🌟 Permits & Compliance (Required na base sa imong frontend)
            'permit_no'          => 'required|string',
            'permit_date_issued' => 'required|date',
            'permit_expiry'      => 'required|date',
            'inspection_status'  => 'required|string',
            'status'             => 'required|in:active,inactive'
        ]);

        $fisher = Fisherfolk::create($request->all());
        
        // 🌟 CALL HELPER FUNCTION
        $this->broadcastBarangayUpdate($fisher->barangay_id);
        
        event(new FisherfolkUpdated($fisher->load('barangay'), 'created'));

        return response()->json([
            'status' => 'success',
            'message' => 'Fisherfolk registered successfully!',
            'data' => $fisher->load('barangay')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fisher = Fisherfolk::findOrFail($id);
        
        // 🌟 I-save ang karaan nga ID para sa update check
        $old_brgy = $fisher->barangay_id;

        // 🌟 UPDATE: Gidugangan og 'sometimes' para sa Partial Updates (e.g. Status toggle only)
        $validated = $request->validate([
            'first_name'         => 'sometimes|required|string',
            'last_name'          => 'sometimes|required|string',
            'gender'             => 'sometimes|required|string',
            'dob'                => 'sometimes|required|date',
            'civil_status'       => 'sometimes|required|string',
            'barangay_id'        => 'sometimes|required|exists:barangays,id',
            'address_details'    => 'sometimes|required|string',
            
            'fisher_type'        => 'sometimes|required|string',
            'years_in_fishing'   => 'sometimes|required|numeric',
            
            'cooperative_id'     => 'nullable|array',
            'boats_list'         => 'nullable|array',
            'assistances_list'   => 'nullable|array',

            'permit_no'          => 'sometimes|required|string',
            'permit_date_issued' => 'sometimes|required|date',
            'permit_expiry'      => 'sometimes|required|date',
            'inspection_status'  => 'sometimes|required|string',
            'status'             => 'sometimes|required|in:active,inactive'
        ]);

        $fisher->update($request->all());
        
        // 🌟 CALL HELPER FUNCTIONS
        $this->broadcastBarangayUpdate($fisher->barangay_id);
        if ($old_brgy != $fisher->barangay_id) {
            $this->broadcastBarangayUpdate($old_brgy);
        }

        event(new FisherfolkUpdated($fisher->load('barangay'), 'updated'));

        return response()->json([
            'status' => 'success',
            'message' => 'Fisherfolk record updated!',
            'data' => $fisher->load('barangay')
        ]);
    }

    public function destroy($id)
    {
        $fisher = Fisherfolk::findOrFail($id);
        
        // 🌟 I-save ang ID usa i-delete
        $brgy_id = $fisher->barangay_id;
        
        $fisher->delete();
        
        // 🌟 CALL HELPER FUNCTION
        $this->broadcastBarangayUpdate($brgy_id);
        
        event(new FisherfolkUpdated($fisher, 'deleted'));
        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully.'
        ]);
    }
}