<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Crop;
use App\Models\Barangay; // 🌟 IMPORTED BARANGAY
use Illuminate\Http\Request;
use App\Events\FarmerUpdated;
use App\Events\CropUpdated;
use App\Events\BarangayUpdated;

class FarmerController extends Controller
{
    // 🌟 HELPER FUNCTION PARA MA-SYNC ANG METRICS SA CROP UG BARANGAY
    private function syncRealtimeMetrics($crop_id, $barangay_id) 
    {
        // 1. UPDATE CROP METRICS
        if ($crop_id) {
            $c = Crop::withCount('registeredFarmers')->with([
                'registeredFarmers' => function($query) { $query->latest(); },
                'registeredFarmers.barangay',
                'registeredFarmers.farmLocation'
            ])->find($crop_id);

            if ($c) {
                $c->farmers = $c->registered_farmers_count;
                $c->registered_farmers = $c->registeredFarmers;
                event(new CropUpdated($c, 'updated'));
            }
        }

        // 2. UPDATE BARANGAY METRICS
        if ($barangay_id) {
            $b = Barangay::with(['farmers', 'fisherfolks', 'cooperatives'])->findOrFail($barangay_id);
            $formatted = [
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
            ];
            event(new BarangayUpdated($formatted, 'updated'));
        }
    }

    public function index() {
        $farmers = Farmer::with(['barangay', 'crop', 'farmLocation', 'cooperative'])->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $farmers]);
    }

    public function store(Request $request) {
        $farmer = Farmer::create($request->all());
        $farmer->load(['barangay', 'crop', 'farmLocation', 'cooperative']);
        
        broadcast(new FarmerUpdated($farmer, 'created'));

        // 🚀 SYNC BOTH CROP AND BARANGAY
        $this->syncRealtimeMetrics($farmer->crop_id, $farmer->barangay_id);

        return response()->json(['data' => $farmer], 201);
    }

    public function update(Request $request, $id) {
        $farmer = Farmer::findOrFail($id);
        $old_crop_id = $farmer->crop_id;
        $old_brgy_id = $farmer->barangay_id;

        $farmer->update($request->all());
        $farmer->load(['barangay', 'crop', 'farmLocation', 'cooperative']);

        broadcast(new FarmerUpdated($farmer, 'updated'))->toOthers();

        // 🚀 SYNC UPDATED CROP & BARANGAY
        $this->syncRealtimeMetrics($farmer->crop_id, $farmer->barangay_id);

        // 🚀 KUNG NABALHIN, SYNC PUD ANG KARAAN
        if ($old_crop_id != $farmer->crop_id) $this->syncRealtimeMetrics($old_crop_id, null);
        if ($old_brgy_id != $farmer->barangay_id) $this->syncRealtimeMetrics(null, $old_brgy_id);

        return response()->json(['data' => $farmer]);
    }

    public function destroy($id)
    {
        $farmer = Farmer::findOrFail($id);
        $crop_id = $farmer->crop_id;
        $brgy_id = $farmer->barangay_id;
        
        broadcast(new FarmerUpdated($farmer, 'deleted'))->toOthers();
        $farmer->delete();

        // 🚀 SYNC PARA MO-MINUS ANG COUNT
        $this->syncRealtimeMetrics($crop_id, $brgy_id);

        return response()->json(['message' => 'Farmer deleted']);
    }
}