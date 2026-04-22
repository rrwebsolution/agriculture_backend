<?php

namespace App\Http\Controllers\Api;

use App\Events\BarangayUpdated;
use App\Events\FisherfolkUpdated;
use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Fisherfolk;
use Illuminate\Http\Request;

class FisherfolkController extends Controller
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

    private function broadcastBarangayUpdate($barangay_id)
    {
        if (!$barangay_id) {
            return;
        }

        $b = Barangay::with([
            'farmers.barangay',
            'farmers.crop',
            'farmers.farmLocation',
            'fisherfolks.barangay',
            'fisherfolks.catchRecords',
            'cooperatives'
        ])->findOrFail($barangay_id);

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
        $newCoopIds = $this->parseCooperativeIds($request->input('cooperative_id', []));

        $validated = $request->validate([
            'system_id' => 'required|unique:fisherfolks,system_id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'civil_status' => 'required|string',
            'barangay_id' => 'required|exists:barangays,id',
            'address_details' => 'required|string',
            'fisher_type' => 'required|string',
            'years_in_fishing' => 'required|numeric',
            'cooperative_id' => 'nullable|array',
            'boats_list' => 'nullable|array',
            'assistances_list' => 'nullable|array',
            'permit_no' => 'required|string',
            'permit_date_issued' => 'required|date',
            'permit_expiry' => 'required|date',
            'inspection_status' => 'required|string',
            'status' => 'required|in:active,inactive'
        ]);

        $fisher = Fisherfolk::create($request->all());
        $fisher = $fisher->fresh(['barangay', 'catchRecords']);

        $this->broadcastBarangayUpdate($fisher->barangay_id);
        event(new FisherfolkUpdated($fisher, 'created'));
        CooperativeController::broadcastCooperativeMembershipUpdateByIds($newCoopIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Fisherfolk registered successfully!',
            'data' => $fisher
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $fisher = Fisherfolk::findOrFail($id);
        $old_brgy = $fisher->barangay_id;
        $oldCoopIds = $this->parseCooperativeIds($fisher->cooperative_id);
        $newCoopIds = $this->parseCooperativeIds($request->input('cooperative_id', $fisher->cooperative_id));

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'gender' => 'sometimes|required|string',
            'dob' => 'sometimes|required|date',
            'civil_status' => 'sometimes|required|string',
            'barangay_id' => 'sometimes|required|exists:barangays,id',
            'address_details' => 'sometimes|required|string',
            'fisher_type' => 'sometimes|required|string',
            'years_in_fishing' => 'sometimes|required|numeric',
            'cooperative_id' => 'nullable|array',
            'boats_list' => 'nullable|array',
            'assistances_list' => 'nullable|array',
            'permit_no' => 'sometimes|required|string',
            'permit_date_issued' => 'sometimes|required|date',
            'permit_expiry' => 'sometimes|required|date',
            'inspection_status' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:active,inactive'
        ]);

        $fisher->update($request->all());
        $fisher = $fisher->fresh(['barangay', 'catchRecords']);

        $this->broadcastBarangayUpdate($fisher->barangay_id);
        if ($old_brgy != $fisher->barangay_id) {
            $this->broadcastBarangayUpdate($old_brgy);
        }

        event(new FisherfolkUpdated($fisher, 'updated'));
        CooperativeController::broadcastCooperativeMembershipUpdateByIds(array_merge($oldCoopIds, $newCoopIds));

        return response()->json([
            'status' => 'success',
            'message' => 'Fisherfolk record updated!',
            'data' => $fisher
        ]);
    }

    public function destroy($id)
    {
        $fisher = Fisherfolk::findOrFail($id);
        $brgy_id = $fisher->barangay_id;
        $coopIds = $this->parseCooperativeIds($fisher->cooperative_id);

        $fisher->delete();

        $this->broadcastBarangayUpdate($brgy_id);
        CooperativeController::broadcastCooperativeMembershipUpdateByIds($coopIds);

        event(new FisherfolkUpdated($fisher, 'deleted'));

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully.'
        ]);
    }
}
