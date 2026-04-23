<?php

namespace App\Http\Controllers\Api;

use App\Events\BarangayUpdated;
use App\Events\CropUpdated;
use App\Events\FarmerUpdated;
use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Crop;
use App\Models\Farmer;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    private function parseCooperativeIds($value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn ($id) => filled($id))
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
            }

            return collect(explode(',', $value))
                ->map(fn ($id) => trim($id))
                ->filter(fn ($id) => $id !== '')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return [];
    }

    private function syncRealtimeMetrics($crop_id, $barangay_id)
    {
        if ($crop_id) {
            $c = Crop::withCount('registeredFarmers')->with([
                'registeredFarmers' => function ($query) {
                    $query->latest();
                },
                'registeredFarmers.barangay',
                'registeredFarmers.farmLocation'
            ])->find($crop_id);

            if ($c) {
                $c->farmers = $c->registered_farmers_count;
                $c->registered_farmers = $c->registeredFarmers;
                event(new CropUpdated($c, 'updated'));
            }
        }

        if ($barangay_id) {
            $b = Barangay::with([
                'farmers.barangay',
                'farmers.crop',
                'farmers.farmLocation',
                'fisherfolks.barangay',
                'fisherfolks.catchRecords',
                'cooperatives'
            ])->findOrFail($barangay_id);

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

    public function index()
    {
        $farmers = Farmer::with(['barangay', 'crop', 'farmLocation', 'plantings', 'harvests'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $farmers]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $newCoopIds = $this->parseCooperativeIds($data['cooperative_id'] ?? []);

        if (isset($data['cooperative_id']) && is_string($data['cooperative_id'])) {
            $data['cooperative_id'] = json_decode($data['cooperative_id'], true);
        }

        if (!empty($data['farms_list']) && count($data['farms_list']) > 0) {
            $firstFarm = $data['farms_list'][0];
            $data['farm_barangay_id'] = $firstFarm['farm_barangay_id'] ?? null;
            $data['farm_sitio'] = $firstFarm['farm_sitio'] ?? null;
            $data['crop_id'] = $firstFarm['crop_id'] ?? null;
            $data['ownership_type'] = $firstFarm['ownership_type'] ?? null;
            $data['total_area'] = $firstFarm['total_area'] ?? 0;
            $data['topography'] = $firstFarm['topography'] ?? null;
            $data['irrigation_type'] = $firstFarm['irrigation_type'] ?? null;
            $data['farm_coordinates'] = $firstFarm['farm_coordinates'] ?? null;
            $data['soil_type'] = $firstFarm['soil_type'] ?? null;
            $data['gpx_file_path'] = $firstFarm['gpx_file_name'] ?? null;
        }

        if (!empty($data['assistances_list']) && count($data['assistances_list']) > 0) {
            $firstProgram = $data['assistances_list'][0];
            $data['program_name'] = $firstProgram['program_name'] ?? null;
            $data['assistance_type'] = $firstProgram['assistance_type'] ?? null;
            $data['date_released'] = $firstProgram['date_released'] ?? null;
            $data['quantity'] = $firstProgram['quantity'] ?? null;
            $data['total_cost'] = $firstProgram['total_cost'] ?? 0;
            $data['funding_source'] = $firstProgram['funding_source'] ?? null;
        }

        $farmer = Farmer::create($data);
        $farmer = $farmer->fresh(['barangay', 'crop', 'farmLocation', 'plantings', 'harvests']);

        event(new FarmerUpdated($farmer, 'created'));
        $this->syncRealtimeMetrics($farmer->crop_id, $farmer->barangay_id);
        CooperativeController::broadcastCooperativeMembershipUpdateByIds($newCoopIds);

        return response()->json(['data' => $farmer], 201);
    }

    public function update(Request $request, $id)
    {
        $farmer = Farmer::findOrFail($id);
        $old_crop_id = $farmer->crop_id;
        $old_brgy_id = $farmer->barangay_id;
        $oldCoopIds = $this->parseCooperativeIds($farmer->cooperative_id);

        $data = $request->all();
        $newCoopIds = $this->parseCooperativeIds($data['cooperative_id'] ?? $farmer->cooperative_id);

        if (isset($data['cooperative_id']) && is_string($data['cooperative_id'])) {
            $data['cooperative_id'] = json_decode($data['cooperative_id'], true);
        }

        if (!empty($data['farms_list']) && count($data['farms_list']) > 0) {
            $firstFarm = $data['farms_list'][0];
            $data['farm_barangay_id'] = $firstFarm['farm_barangay_id'] ?? null;
            $data['farm_sitio'] = $firstFarm['farm_sitio'] ?? null;
            $data['crop_id'] = $firstFarm['crop_id'] ?? null;
            $data['ownership_type'] = $firstFarm['ownership_type'] ?? null;
            $data['total_area'] = $firstFarm['total_area'] ?? 0;
            $data['topography'] = $firstFarm['topography'] ?? null;
            $data['irrigation_type'] = $firstFarm['irrigation_type'] ?? null;
            $data['farm_coordinates'] = $firstFarm['farm_coordinates'] ?? null;
            $data['soil_type'] = $firstFarm['soil_type'] ?? null;
            $data['gpx_file_path'] = $firstFarm['gpx_file_name'] ?? null;
        }

        if (!empty($data['assistances_list']) && count($data['assistances_list']) > 0) {
            $firstProgram = $data['assistances_list'][0];
            $data['program_name'] = $firstProgram['program_name'] ?? null;
            $data['assistance_type'] = $firstProgram['assistance_type'] ?? null;
            $data['date_released'] = $firstProgram['date_released'] ?? null;
            $data['quantity'] = $firstProgram['quantity'] ?? null;
            $data['total_cost'] = $firstProgram['total_cost'] ?? 0;
            $data['funding_source'] = $firstProgram['funding_source'] ?? null;
        }

        $farmer->update($data);
        $farmer = $farmer->fresh(['barangay', 'crop', 'farmLocation', 'plantings', 'harvests']);

        event(new FarmerUpdated($farmer, 'updated'));

        $farmer->plantings->each(function ($planting) {
            $p = $planting->fresh(['farmer', 'barangay', 'crop', 'statusHistory']);
            event(new \App\Events\PlantingUpdated($p, 'updated'));
        });

        $this->syncRealtimeMetrics($farmer->crop_id, $farmer->barangay_id);
        CooperativeController::broadcastCooperativeMembershipUpdateByIds(array_merge($oldCoopIds, $newCoopIds));

        if ($old_crop_id != $farmer->crop_id) {
            $this->syncRealtimeMetrics($old_crop_id, null);
        }

        if ($old_brgy_id != $farmer->barangay_id) {
            $this->syncRealtimeMetrics(null, $old_brgy_id);
        }

        return response()->json(['data' => $farmer]);
    }

    public function destroy($id)
    {
        $farmer = Farmer::findOrFail($id);
        $crop_id = $farmer->crop_id;
        $brgy_id = $farmer->barangay_id;
        $coopIds = $this->parseCooperativeIds($farmer->cooperative_id);

        event(new FarmerUpdated($farmer, 'deleted'));
        $farmer->delete();

        $this->syncRealtimeMetrics($crop_id, $brgy_id);
        CooperativeController::broadcastCooperativeMembershipUpdateByIds($coopIds);

        return response()->json(['message' => 'Farmer deleted']);
    }
}
