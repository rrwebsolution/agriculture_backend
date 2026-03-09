<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Request;
use App\Events\CropUpdated; // 🌟 I-IMPORT ANG EVENT

class CropController extends Controller
{
    public function index()
    {
        $crops = Crop::latest()
            ->withCount('registeredFarmers')
            ->with([
                'registeredFarmers' => function($query) {
                    $query->latest();
                },
                'registeredFarmers.barangay',
                'registeredFarmers.farmLocation'
            ])
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
            'category' => 'required|string|max:255',
            'remarks'  => 'required|string',
        ]);

        $crop = Crop::create($validatedData);

        // 🌟 I-format ang data para matches sa Frontend
        $crop->farmers = 0;
        $crop->registered_farmers = [];

        // 🌟 BROADCAST EVENT: CREATED
        event(new CropUpdated($crop, 'created'));

        return response()->json([
            'message' => 'Land record successfully created!',
            'data'    => $crop
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $crop = Crop::findOrFail($id);

        $validatedData = $request->validate([
            'category' => 'required|string|max:255',
            'remarks'  => 'required|string',
        ]);

        $crop->update($validatedData);

        // 🌟 I-load balik ang relationships ug format
        $crop->loadCount('registeredFarmers');
        $crop->load([
            'registeredFarmers' => function($query) {
                $query->latest();
            },
            'registeredFarmers.barangay',
            'registeredFarmers.farmLocation'
        ]);
        
        $crop->farmers = $crop->registered_farmers_count;
        $crop->registered_farmers = $crop->registeredFarmers;

        // 🌟 BROADCAST EVENT: UPDATED
        event(new CropUpdated($crop, 'updated'));

        return response()->json([
            'message' => 'Land record successfully updated!',
            'data'    => $crop
        ]);
    }

    public function destroy($id)
    {
        $crop = Crop::withCount('registeredFarmers')->findOrFail($id);

        if ($crop->registered_farmers_count > 0) {
            return response()->json([
                'message' => 'Cannot delete this record because it is currently assigned to ' . $crop->registered_farmers_count . ' farmer(s).'
            ], 422); 
        }

        $crop->delete();

        // 🌟 BROADCAST EVENT: DELETED
        event(new CropUpdated($crop, 'deleted'));

        return response()->json([
            'message' => 'Land record successfully deleted!'
        ]);
    }
}