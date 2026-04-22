<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\Request;
use App\Events\BarangayUpdated;
use Illuminate\Support\Facades\Log; // 🌟 Gidugang para sa debugging

class BarangayController extends Controller
{
    private function mapCooperativesWithMembers($cooperatives)
    {
        $allFarmers = \App\Models\Farmer::with(['barangay', 'crop', 'farmLocation'])->whereNotNull('cooperative_id')->get();
        $allFisherfolks = \App\Models\Fisherfolk::with(['barangay', 'catchRecords'])->whereNotNull('cooperative_id')->get();
        $crops = \App\Models\Crop::pluck('category', 'id');

        return $cooperatives->map(function ($coop) use ($allFarmers, $allFisherfolks, $crops) {
            $assignedFarmers = $allFarmers->filter(function ($farmer) use ($coop) {
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
            })->values();

            $assignedFisherfolks = $allFisherfolks->filter(function ($fisher) use ($coop) {
                $coopIds = (array) $fisher->cooperative_id;
                return in_array($coop->id, $coopIds) || in_array((string)$coop->id, $coopIds);
            })->values();

            $coop->assigned_farmers_count = $assignedFarmers->count();
            $coop->assigned_farmers_list = $assignedFarmers;
            
            $coop->assigned_fisherfolks_count = $assignedFisherfolks->count();
            $coop->assigned_fisherfolks_list = $assignedFisherfolks;

            return $coop;
        });
    }

    private function broadcastBarangayUpdate($barangay_id)
    {
        try {
            $b = \App\Models\Barangay::withCount([
                'farmers', 
                'fisherfolks', 
                'cooperatives',
                'plantings'
            ])->findOrFail($barangay_id);

            $formatted = [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'type' => $b->type,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'farmers_count' => $b->farmers_count, 
                'fisherfolks_count' => $b->fisherfolks_count,
                'cooperatives_count' => $b->cooperatives_count,
                'plantings_count' => $b->plantings_count,
                'is_lite' => true 
            ];

            // 🌟 GAMITON ANG broadcast() imbes event() para sure nga mo-send sa Websocket
            broadcast(new BarangayUpdated($formatted, 'updated'));
            
            // 🐛 I-save sa log aron makita nimo kung ni-fire
            Log::info('✅ Barangay Broadcast Sent:', ['id' => $b->id, 'name' => $b->name]);

        } catch (\Exception $e) {
            Log::error('❌ Error broadcasting Barangay: ' . $e->getMessage());
        }
    }
    
    public function index()
    {
        $barangays = Barangay::with([
            'farmers.barangay',
            'farmers.farmLocation', 
            'farmers.crop', 
            'fisherfolks.barangay',
            'fisherfolks.catchRecords',
            'cooperatives',
            'harvests.crop', 
            'harvests.farmer',
            'plantings.crop',           
            'plantings.farmer',         
            'plantings.statusHistory', 
        ])->get();

        $formattedData = $barangays->map(function ($b) {
            $mappedCooperatives = $this->mapCooperativesWithMembers($b->cooperatives);

            return [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'type' => $b->type,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'farmers' => $b->farmers->count(),
                'fisherfolks' => $b->fisherfolks->count(),
                'cooperatives_count' => $b->cooperatives_count, 
                'farmersList' => $b->farmers,
                'fisherfolksList' => $b->fisherfolks,
                'cooperativesList' => $mappedCooperatives,
                'harvests' => $b->harvests,
                'plantingLogs' => $b->plantings
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedData,
            'metrics' => [
                'total' => $barangays->count(),
                'urban' => $barangays->where('type', 'Urban (Poblacion)')->count(),
                'rural' => $barangays->where('type', 'Rural')->count(),
                'coastal' => $barangays->where('type', 'Coastal')->count(),
            ]
        ]);
    }

    public function update(Request $request, $id)
{
    $barangay = Barangay::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|unique:barangays,name,' . $id, 
        'type' => 'required|in:Urban (Poblacion),Rural,Coastal',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
    ]);

    $barangay->update($validated);

    // 🌟 KINI ANG SOLUSYON:
    // Kinahanglan nato i-notify ang Planting Module nga nausab ang info sa Barangay.
    // Atong i-loop ang tanang plantings nga sakop aning barangaya.
    $barangay->plantings->each(function ($planting) {
        // I-load ang kumpletong relationships para dili "lite" data ang makuha sa frontend
        $p = $planting->fresh(['farmer', 'barangay', 'crop', 'statusHistory']);
        
        // I-broadcast as PlantingUpdated aron mo-refresh ang row sa Planting Table
        broadcast(new \App\Events\PlantingUpdated($p, 'updated'));
    });

    // I-broadcast gihapon ang BarangayUpdated para sa mga dropdowns/sidebar
    $this->broadcastBarangayUpdate($barangay->id);

    return response()->json([
        'status' => 'success',
        'message' => 'Barangay ' . $barangay->name . ' updated successfully!',
        'data' => $barangay
    ], 200);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:barangays,name',
            'type' => 'required|in:Urban (Poblacion),Rural,Coastal',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $barangay = Barangay::create($validated);
        $this->broadcastBarangayUpdate($barangay->id);

        return response()->json([
            'status' => 'success',
            'message' => 'New Barangay added with code: ' . $barangay->code,
            'data' => $barangay
        ], 201);
    }

    public function destroy($id)
    {
        $barangay = Barangay::findOrFail($id);
        
        // Sa dili pa i-delete, i-notify ang planting module
        $barangay->plantings->each(function ($planting) {
            broadcast(new \App\Events\PlantingUpdated($planting->id, 'deleted'));
        });

        $barangay->delete();
        broadcast(new BarangayUpdated($barangay, 'deleted'));

        return response()->json([
            'status' => 'success',
            'message' => 'Barangay deleted successfully!'
        ]);
    }
}
