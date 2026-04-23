<?php

namespace App\Http\Controllers\Api;

use App\Events\FisheryUpdated;
use App\Http\Controllers\Controller;
use App\Models\FisheryRecord;
use Illuminate\Http\Request;

class FisheryRecordController extends Controller
{
    public function index()
    {
        $records = FisheryRecord::orderBy('date', 'desc')->get()->map(
            fn (FisheryRecord $record) => $this->transformRecord($record)
        );

        return response()->json(['data' => $records], 200);
    }

    public function store(Request $request)
    {
        $record = FisheryRecord::create($this->buildPayload($request))->fresh();
        event(new FisheryUpdated($record, 'created'));

        return response()->json([
            'message' => 'Catch record saved successfully.',
            'data' => $this->transformRecord($record),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = FisheryRecord::findOrFail($id);
        $record->update($this->buildPayload($request));
        $record = $record->fresh();

        event(new FisheryUpdated($record, 'updated'));

        return response()->json([
            'message' => 'Catch record updated successfully.',
            'data' => $this->transformRecord($record),
        ], 200);
    }

    public function destroy($id)
    {
        $record = FisheryRecord::findOrFail($id);
        $record->delete();
        event(new FisheryUpdated($record, 'deleted'));

        return response()->json(['message' => 'Catch record deleted successfully.'], 200);
    }

    private function buildPayload(Request $request): array
    {
        $validated = $request->validate([
            'fishr_id' => 'required|string',
            'name' => 'required|string',
            'gender' => 'required|string',
            'contact_no' => 'nullable|string',
            'date' => 'required|date',
            'hours_spent_fishing' => 'nullable|numeric|min:0',
            'vessel_catch_entries' => 'nullable|array|min:1',
            'vessel_catch_entries.*.boat_name' => 'nullable|string',
            'vessel_catch_entries.*.gear_type' => 'required_with:vessel_catch_entries|string',
            'vessel_catch_entries.*.fishing_area' => 'required_with:vessel_catch_entries|string',
            'vessel_catch_entries.*.catch_species' => 'required_with:vessel_catch_entries|string',
            'vessel_catch_entries.*.yield' => 'required_with:vessel_catch_entries|numeric|min:0',
            'vessel_catch_entries.*.market_value' => 'required_with:vessel_catch_entries|numeric|min:0',
            'vessel_catch_entries.*.hours_spent_fishing' => 'nullable|numeric|min:0',
            'boat_name' => 'nullable|string',
            'gear_type' => 'nullable|string',
            'fishing_area' => 'nullable|string',
            'catch_species' => 'nullable|string',
            'yield' => 'nullable|numeric|min:0',
            'market_value' => 'nullable|numeric|min:0',
        ]);

        $entries = collect($validated['vessel_catch_entries'] ?? [])->map(function (array $entry) {
            return [
                'boat_name' => $entry['boat_name'] ?? null,
                'gear_type' => $entry['gear_type'] ?? '',
                'fishing_area' => $entry['fishing_area'] ?? '',
                'catch_species' => $entry['catch_species'] ?? '',
                'yield' => (float) ($entry['yield'] ?? 0),
                'market_value' => (float) ($entry['market_value'] ?? 0),
                'hours_spent_fishing' => isset($entry['hours_spent_fishing']) ? (float) $entry['hours_spent_fishing'] : null,
            ];
        })->filter(fn (array $entry) => filled($entry['gear_type']) || filled($entry['catch_species']))->values();

        if ($entries->isEmpty()) {
            $request->validate([
                'gear_type' => 'required|string',
                'fishing_area' => 'required|string',
                'catch_species' => 'required|string',
                'yield' => 'required|numeric|min:0',
                'market_value' => 'required|numeric|min:0',
            ]);

            $entries = collect([[
                'boat_name' => $validated['boat_name'] ?? null,
                'gear_type' => $validated['gear_type'],
                'fishing_area' => $validated['fishing_area'],
                'catch_species' => $validated['catch_species'],
                'yield' => (float) ($validated['yield'] ?? 0),
                'market_value' => (float) ($validated['market_value'] ?? 0),
                'hours_spent_fishing' => isset($validated['hours_spent_fishing']) ? (float) $validated['hours_spent_fishing'] : null,
            ]]);
        }

        $primaryEntry = $entries->first();
        $totalHours = isset($validated['hours_spent_fishing'])
            ? (float) $validated['hours_spent_fishing']
            : (float) $entries->sum(fn (array $entry) => (float) ($entry['hours_spent_fishing'] ?? 0));

        return [
            'fishr_id' => $validated['fishr_id'],
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'contact_no' => $validated['contact_no'] ?? null,
            'boat_name' => $primaryEntry['boat_name'] ?? null,
            'gear_type' => $primaryEntry['gear_type'] ?? null,
            'fishing_area' => $primaryEntry['fishing_area'] ?? null,
            'catch_species' => $primaryEntry['catch_species'] ?? null,
            'yield' => (float) $entries->sum('yield'),
            'market_value' => (float) $entries->sum('market_value'),
            'hours_spent_fishing' => $totalHours,
            'vessel_catch_entries' => $entries->all(),
            'date' => $validated['date'],
        ];
    }

    private function transformRecord(FisheryRecord $record): array
    {
        $entries = collect($record->vessel_catch_entries ?: [])->values();

        if ($entries->isEmpty()) {
            $entries = collect([[
                'boat_name' => $record->boat_name,
                'gear_type' => $record->gear_type,
                'fishing_area' => $record->fishing_area,
                'catch_species' => $record->catch_species,
                'yield' => (float) $record->yield,
                'market_value' => (float) $record->market_value,
                'hours_spent_fishing' => $record->hours_spent_fishing,
            ]]);
        }

        return [
            'id' => $record->id,
            'fishr_id' => $record->fishr_id,
            'name' => $record->name,
            'gender' => $record->gender,
            'contact_no' => $record->contact_no,
            'boat_name' => $record->boat_name,
            'gear_type' => $record->gear_type,
            'fishing_area' => $record->fishing_area,
            'catch_species' => $record->catch_species,
            'yield' => (float) $record->yield,
            'market_value' => (float) $record->market_value,
            'hours_spent_fishing' => (float) ($record->hours_spent_fishing ?? 0),
            'vessel_catch_entries' => $entries->all(),
            'date' => optional($record->date)->format('Y-m-d'),
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];
    }
}
