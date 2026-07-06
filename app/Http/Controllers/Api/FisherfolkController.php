<?php

namespace App\Http\Controllers\Api;

use App\Events\BarangayUpdated;
use App\Events\FisherfolkUpdated;
use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Fisherfolk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FisherfolkController extends Controller
{
    private const PROFILE_PHOTO_MAX_KB = 2048;

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

    private function decodeJsonArrayFields(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            $value = $request->input($field);

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }

    private function storeProfilePhoto(Request $request, ?Fisherfolk $fisher = null): ?string
    {
        if (!$request->hasFile('profile_photo')) {
            return $fisher?->profile_photo_path;
        }

        $file = $request->file('profile_photo');
        $directory = public_path('uploads/profile-photos/fisherfolks');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($fisher?->profile_photo_path) {
            File::delete(public_path($fisher->profile_photo_path));
        }

        $filename = uniqid('fisherfolk_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/profile-photos/fisherfolks/' . $filename;
    }

    public function index()
    {
        $records = Fisherfolk::with(['barangay', 'catchRecords'])->latest()->get();
        return response()->json(['status' => 'success', 'data' => $records]);
    }

    public function store(Request $request)
    {
        $this->decodeJsonArrayFields($request, ['cooperative_id', 'boats_list', 'assistances_list']);
        $newCoopIds = $this->parseCooperativeIds($request->input('cooperative_id', []));

        $validated = $request->validate([
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::PROFILE_PHOTO_MAX_KB],
            'system_id' => 'required|unique:fisherfolks,system_id',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'civil_status' => 'nullable|string',
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
        ], [
            'profile_photo.max' => 'Profile photo must not be greater than 2MB.',
        ]);

        $data = $request->all();
        unset($data['profile_photo'], $data['profile_photo_url']);
        $data['profile_photo_path'] = $this->storeProfilePhoto($request);

        $fisher = Fisherfolk::create($data);
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
        $this->decodeJsonArrayFields($request, ['cooperative_id', 'boats_list', 'assistances_list']);
        $fisher = Fisherfolk::findOrFail($id);
        $old_brgy = $fisher->barangay_id;
        $oldCoopIds = $this->parseCooperativeIds($fisher->cooperative_id);
        $newCoopIds = $this->parseCooperativeIds($request->input('cooperative_id', $fisher->cooperative_id));

        $validated = $request->validate([
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::PROFILE_PHOTO_MAX_KB],
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'gender' => 'sometimes|required|string',
            'dob' => 'sometimes|required|date',
            'civil_status' => 'nullable|string',
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
        ], [
            'profile_photo.max' => 'Profile photo must not be greater than 2MB.',
        ]);

        $data = $request->all();
        unset($data['profile_photo'], $data['profile_photo_url'], $data['_method']);
        $data['profile_photo_path'] = $this->storeProfilePhoto($request, $fisher);

        $fisher->update($data);
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
