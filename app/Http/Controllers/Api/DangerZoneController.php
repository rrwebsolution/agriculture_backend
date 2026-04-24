<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DangerZone;
use Illuminate\Http\Request;

class DangerZoneController extends Controller
{
    public function index()
    {
        $zones = DangerZone::latest()->get()->map(function (DangerZone $zone) {
            return $this->transformZone($zone);
        });

        return response()->json(['data' => $zones]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $zone = DangerZone::create($validated);

        return response()->json(['data' => $this->transformZone($zone)], 201);
    }

    public function update(Request $request, $id)
    {
        $zone = DangerZone::findOrFail($id);
        $validated = $this->validatePayload($request);
        $zone->update($validated);

        return response()->json(['data' => $this->transformZone($zone->fresh())]);
    }

    public function destroy($id)
    {
        $zone = DangerZone::findOrFail($id);
        $zone->delete();

        return response()->json(['message' => 'Danger zone deleted successfully.']);
    }

    protected function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
            'color' => ['nullable', 'string', 'max:20'],
            'fill_color' => ['nullable', 'string', 'max:20'],
            'positions' => ['required', 'array', 'min:3'],
            'positions.*.lat' => ['required', 'numeric'],
            'positions.*.lng' => ['required', 'numeric'],
        ]);

        $validated['positions'] = collect($validated['positions'])
            ->map(function (array $position) {
                return [
                    'lat' => (float) $position['lat'],
                    'lng' => (float) $position['lng'],
                ];
            })
            ->values()
            ->all();

        $validated['color'] = $validated['color'] ?? '#dc2626';
        $validated['fill_color'] = $validated['fill_color'] ?? '#f87171';

        return $validated;
    }

    protected function transformZone(DangerZone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'zone_type' => $zone->zone_type,
            'description' => $zone->description,
            'status' => $zone->status,
            'color' => $zone->color,
            'fill_color' => $zone->fill_color,
            'positions' => collect($zone->positions ?? [])->map(function ($position) {
                return [
                    'lat' => (float) data_get($position, 'lat', 0),
                    'lng' => (float) data_get($position, 'lng', 0),
                ];
            })->values()->all(),
            'created_at' => optional($zone->created_at)->toISOString(),
            'updated_at' => optional($zone->updated_at)->toISOString(),
        ];
    }
}
