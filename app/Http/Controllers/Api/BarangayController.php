<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\Request;
use App\Events\BarangayUpdated;

class BarangayController extends Controller
{
    private function broadcastBarangayUpdate($barangay_id)
    {
        $b = \App\Models\Barangay::with(['farmers', 'fisherfolks', 'cooperatives'])->findOrFail($barangay_id);

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

        event(new \App\Events\BarangayUpdated($formatted, 'updated'));
    }
    
    public function index()
    {
        // 🌟 Eager load tanang relasyon: Farmers, fisherfolks, ug Cooperatives
        // Siguroha nga kini nga mga relationship names nag-exist sa imong Barangay Model
        $barangays = Barangay::with([
            'farmers.farmLocation', 
            'farmers.cooperative',
            'fisherfolks', // I-adjust kung unsa ang ngalan sa relasyon sa fisherfolks
            'cooperatives'            // Listahan sa mga kooperatiba sa maong barangay
        ])->get();

        $formattedData = $barangays->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'type' => $b->type,
                
                // --- COUNTS PARA SA TABLE ---
                'farmers' => $b->farmers->count(),
                'fisherfolks' => $b->fisherfolks->count(),
                'cooperatives_count' => $b->cooperatives->count(), // Gi-match sa frontend key
                
                // --- LISTS PARA SA MODAL ---
                'farmersList' => $b->farmers,
                'fisherfolksList' => $b->fisherfolks,
                'cooperativesList' => $b->cooperatives
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
        // 1. Pangitaon ang barangay base sa ID
        $barangay = Barangay::findOrFail($id);

        // 2. I-validate ang datus nga gikan sa React (Frontend)
        $validated = $request->validate([
            'name' => 'required|string|unique:barangays,name,' . $id, // I-ignore ang iyang kaugalingon nga ID
            'type' => 'required|in:Urban (Poblacion),Rural,Coastal',
        ]);

        // 3. I-update ang datus sa database
        // Siguroha nga kini nga mga fields naa sa $fillable sa imong Barangay Model
        $barangay->update($validated);
        event(new BarangayUpdated($barangay, 'updated'));

        // 4. I-return ang success response
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
        ]);

        // Inig tawag niini, mo-trigger ang 'creating' event sa Model para sa Code
        $barangay = Barangay::create($validated);
        event(new BarangayUpdated($barangay, 'created'));

        return response()->json([
            'status' => 'success',
            'message' => 'New Barangay added with code: ' . $barangay->code,
            'data' => $barangay
        ], 201);
    }

    public function destroy($id)
    {
        $barangay = Barangay::findOrFail($id);

        $barangay->delete();

        event(new BarangayUpdated($barangay, 'deleted'));

        return response()->json([
            'status' => 'success',
            'message' => 'Barangay deleted successfully!'
        ]);
    }
}