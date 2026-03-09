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
        $records = Fisherfolk::with('barangay')->latest()->get();
        return response()->json(['status' => 'success', 'data' => $records]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id'     => 'required|unique:fisherfolks,system_id',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'gender'        => 'required',
            'dob'           => 'required|date',
            'barangay_id'   => 'required|exists:barangays,id',
            'fisher_type'   => 'required',
            'status'        => 'required|in:active,inactive'
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

        $validated = $request->validate([
            'first_name'    => 'sometimes|required',
            'last_name'     => 'sometimes|required',
            'barangay_id'   => 'sometimes|required|exists:barangays,id',
            'status'        => 'sometimes|required|in:active,inactive'
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