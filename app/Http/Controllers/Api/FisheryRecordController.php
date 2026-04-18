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
        $records = FisheryRecord::orderBy('date', 'desc')->get();
        return response()->json(['data' => $records], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fishr_id'      => 'required|string',
            'name'          => 'required|string',
            'gender'        => 'required|string',
            'contact_no'    => 'nullable|string',
            'boat_name'     => 'nullable|string',
            'gear_type'     => 'required|string',
            'fishing_area'  => 'required|string',
            'catch_species' => 'required|string',
            'yield'         => 'required|numeric',
            'market_value'  => 'required|numeric', // 🌟 Gidugang
            'date'          => 'required|date',
        ]);

        $record = FisheryRecord::create($validated);
        event(new FisheryUpdated($record, 'created'));
        return response()->json(['message' => 'Catch record saved successfully.', 'data' => $record], 201);
    }

    public function update(Request $request, $id)
    {
        $record = FisheryRecord::findOrFail($id);

        $validated = $request->validate([
            'fishr_id'      => 'required|string',
            'name'          => 'required|string',
            'gender'        => 'required|string',
            'contact_no'    => 'nullable|string',
            'boat_name'     => 'nullable|string',
            'gear_type'     => 'required|string',
            'fishing_area'  => 'required|string',
            'catch_species' => 'required|string',
            'yield'         => 'required|numeric',
            'market_value'  => 'required|numeric', // 🌟 Gidugang
            'date'          => 'required|date',
        ]);

        $record->update($validated);
        event(new FisheryUpdated($record, 'updated'));
        return response()->json(['message' => 'Catch record updated successfully.', 'data' => $record], 200);
    }

    public function destroy($id)
    {
        $record = FisheryRecord::findOrFail($id);
        $record->delete();
        event(new FisheryUpdated($record, 'deleted'));
        return response()->json(['message' => 'Catch record deleted successfully.'], 200);
    }
}
