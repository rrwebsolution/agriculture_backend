<?php

namespace App\Http\Controllers\Api;

use App\Events\CropUpdated;
use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CropController extends Controller
{
    public function index()
    {
        $crops = Crop::latest()
            ->withCount('registeredFarmers')
            ->with([
                'registeredFarmers' => function ($query) {
                    $query->latest();
                },
                'registeredFarmers.barangay',
                'registeredFarmers.farmLocation',
            ])->orderBy('id', 'asc')
            ->get();

        $crops->map(function ($crop) {
            $crop->farmers = $crop->registered_farmers_count;

            return $crop;
        });

        return response()->json(['data' => $crops]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category' => ['required', 'string', 'max:255', Rule::unique('crops', 'category')],
            'crop_names' => ['required', 'string', 'max:2000'],
            'remarks' => ['required', 'string'],
        ]);

        $crop = Crop::create($validatedData);
        $crop->farmers = 0;
        $crop->registered_farmers = [];

        event(new CropUpdated($crop, 'created'));

        return response()->json([
            'message' => 'Land record successfully created!',
            'data' => $crop,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $crop = Crop::findOrFail($id);

        $validatedData = $request->validate([
            'category' => ['required', 'string', 'max:255', Rule::unique('crops', 'category')->ignore($crop->id)],
            'crop_names' => ['required', 'string', 'max:2000'],
            'remarks' => ['required', 'string'],
        ]);

        $crop->update($validatedData);

        if ($crop->plantings) {
            $crop->plantings->each(function ($planting) {
                $planting = $planting->fresh(['farmer', 'barangay', 'crop', 'statusHistory']);
                broadcast(new \App\Events\PlantingUpdated($planting, 'updated'));
            });
        }

        $crop->loadCount('registeredFarmers');
        $crop->load([
            'registeredFarmers' => function ($query) {
                $query->latest();
            },
            'registeredFarmers.barangay',
            'registeredFarmers.farmLocation',
        ]);

        $crop->farmers = $crop->registered_farmers_count;
        $crop->registered_farmers = $crop->registeredFarmers;

        broadcast(new CropUpdated($crop, 'updated'));

        return response()->json([
            'message' => 'Land record successfully updated!',
            'data' => $crop,
        ]);
    }

    public function addCropType(Request $request, Crop $crop)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/,/'],
        ], [
            'name.not_regex' => 'Add one crop type or variety at a time.',
        ]);

        $name = preg_replace('/\s+/', ' ', trim($validated['name']));

        [$updatedCrop, $savedName, $created] = DB::transaction(function () use ($crop, $name) {
            $lockedCrop = Crop::query()->lockForUpdate()->findOrFail($crop->id);
            $cropTypes = collect(explode(',', (string) $lockedCrop->crop_names))
                ->map(fn ($type) => trim($type))
                ->filter()
                ->values();

            $existingName = $cropTypes->first(
                fn ($type) => strcasecmp($type, $name) === 0
            );

            if (! $existingName) {
                $cropTypes->push($name);
                $lockedCrop->update(['crop_names' => $cropTypes->implode(', ')]);
            }

            return [$lockedCrop->fresh(), $existingName ?: $name, ! $existingName];
        });

        $updatedCrop->loadCount('registeredFarmers');
        $updatedCrop->load([
            'registeredFarmers' => fn ($query) => $query->latest(),
            'registeredFarmers.barangay',
            'registeredFarmers.farmLocation',
        ]);
        $updatedCrop->farmers = $updatedCrop->registered_farmers_count;

        broadcast(new CropUpdated($updatedCrop, 'updated'));

        return response()->json([
            'message' => $created ? 'Crop type added successfully.' : 'Crop type already exists.',
            'crop_type' => $savedName,
            'data' => $updatedCrop,
        ], $created ? 201 : 200);
    }

    public function destroy($id)
    {
        $crop = Crop::withCount('registeredFarmers')->findOrFail($id);

        if ($crop->registered_farmers_count > 0) {
            return response()->json([
                'message' => 'Cannot delete this record because it is currently assigned to '.$crop->registered_farmers_count.' farmer(s).',
            ], 422);
        }

        $crop->delete();
        event(new CropUpdated($crop, 'deleted'));

        return response()->json([
            'message' => 'Land record successfully deleted!',
        ]);
    }
}
