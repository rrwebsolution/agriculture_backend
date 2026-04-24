<?php

namespace App\Http\Controllers\Api;

use App\Events\FisheryUpdated;
use App\Http\Controllers\Controller;
use App\Models\FisheryRecord;
use Illuminate\Http\Request;

class FisheryRecordController extends Controller
{
    private function normalizeSpecies(string|array|null $species): array
    {
        if (is_array($species)) {
            return collect($species)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        return collect(explode(',', (string) $species))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function serializeSpecies(string|array|null $species): string
    {
        return collect($this->normalizeSpecies($species))->implode(', ');
    }

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
        $this->broadcastFisheryUpdate($record, 'created');

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

        $this->broadcastFisheryUpdate($record, 'updated');

        return response()->json([
            'message' => 'Catch record updated successfully.',
            'data' => $this->transformRecord($record),
        ], 200);
    }

    public function destroy($id)
    {
        $record = FisheryRecord::findOrFail($id);
        $record->delete();
        $this->broadcastFisheryUpdate($record, 'deleted');

        return response()->json(['message' => 'Catch record deleted successfully.'], 200);
    }

    private function broadcastFisheryUpdate(FisheryRecord $record, string $type): void
    {
        try {
            event(new FisheryUpdated($record, $type));
        } catch (\Throwable $exception) {
            report($exception);
        }
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
            'vessel_catch_entries.*.catch_date' => 'required_with:vessel_catch_entries|date',
            'vessel_catch_entries.*.catch_time_from' => 'required_with:vessel_catch_entries|date_format:H:i',
            'vessel_catch_entries.*.catch_time_to' => 'required_with:vessel_catch_entries|date_format:H:i',
            'vessel_catch_entries.*.catch_species' => 'required_with:vessel_catch_entries',
            'vessel_catch_entries.*.yield' => 'required_with:vessel_catch_entries|numeric|min:0',
            'vessel_catch_entries.*.market_value' => 'required_with:vessel_catch_entries|numeric|min:0',
            'vessel_catch_entries.*.hours_spent_fishing' => 'nullable|numeric|min:0',
            'boat_name' => 'nullable|string',
            'gear_type' => 'nullable|string',
            'fishing_area' => 'nullable|string',
            'catch_species' => 'nullable',
            'yield' => 'nullable|numeric|min:0',
            'market_value' => 'nullable|numeric|min:0',
        ]);

        $entries = collect($validated['vessel_catch_entries'] ?? [])->map(function (array $entry) {
            return [
                'boat_name' => $entry['boat_name'] ?? null,
                'gear_type' => $entry['gear_type'] ?? '',
                'fishing_area' => $entry['fishing_area'] ?? '',
                'catch_date' => $entry['catch_date'] ?? null,
                'catch_time_from' => $entry['catch_time_from'] ?? null,
                'catch_time_to' => $entry['catch_time_to'] ?? null,
                'catch_species' => $this->serializeSpecies($entry['catch_species'] ?? ''),
                'catch_species_list' => $this->normalizeSpecies($entry['catch_species'] ?? ''),
                'yield' => (float) ($entry['yield'] ?? 0),
                'market_value' => (float) ($entry['market_value'] ?? 0),
                'hours_spent_fishing' => isset($entry['hours_spent_fishing']) ? (float) $entry['hours_spent_fishing'] : null,
            ];
        })->filter(fn (array $entry) => filled($entry['gear_type']) || filled($entry['catch_species']))->values();

        if ($entries->isEmpty()) {
            $request->validate([
                'gear_type' => 'required|string',
                'fishing_area' => 'required|string',
                'catch_species' => 'required',
                'yield' => 'required|numeric|min:0',
                'market_value' => 'required|numeric|min:0',
            ]);

            $entries = collect([[
                'boat_name' => $validated['boat_name'] ?? null,
                'gear_type' => $validated['gear_type'],
                'fishing_area' => $validated['fishing_area'],
                'catch_date' => $validated['date'],
                'catch_time_from' => '08:00',
                'catch_time_to' => '12:00',
                'catch_species' => $this->serializeSpecies($validated['catch_species']),
                'catch_species_list' => $this->normalizeSpecies($validated['catch_species']),
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
            'catch_species' => $this->serializeSpecies($primaryEntry['catch_species'] ?? null),
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
                'catch_date' => optional($record->date)->format('Y-m-d'),
                'catch_time_from' => optional($record->created_at)->format('H:i') ?: '08:00',
                'catch_time_to' => optional($record->created_at)->copy()?->addHours(4)?->format('H:i') ?: '12:00',
                'catch_species' => $record->catch_species,
                'catch_species_list' => $this->normalizeSpecies($record->catch_species),
                'yield' => (float) $record->yield,
                'market_value' => (float) $record->market_value,
                'hours_spent_fishing' => $record->hours_spent_fishing,
            ]]);
        }

        $normalizedEntries = $entries->map(function (array $entry) {
            $serializedSpecies = $this->serializeSpecies($entry['catch_species'] ?? '');

            return [
                ...$entry,
                'catch_species' => $serializedSpecies,
                'catch_species_list' => $this->normalizeSpecies($serializedSpecies),
            ];
        })->all();

        return [
            'id' => $record->id,
            'fishr_id' => $record->fishr_id,
            'name' => $record->name,
            'gender' => $record->gender,
            'contact_no' => $record->contact_no,
            'boat_name' => $record->boat_name,
            'gear_type' => $record->gear_type,
            'fishing_area' => $record->fishing_area,
            'catch_species' => $this->serializeSpecies($record->catch_species),
            'catch_species_list' => $this->normalizeSpecies($record->catch_species),
            'yield' => (float) $record->yield,
            'market_value' => (float) $record->market_value,
            'hours_spent_fishing' => (float) ($record->hours_spent_fishing ?? 0),
            'vessel_catch_entries' => $normalizedEntries,
            'date' => optional($record->date)->format('Y-m-d'),
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];
    }
}
