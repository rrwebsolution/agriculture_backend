<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NurseryRecord;
use Illuminate\Http\Request;

class NurseryRecordController extends Controller
{
    public function index()
    {
        $records = NurseryRecord::orderByDesc('record_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $records]);
    }

    public function store(Request $request)
    {
        $record = NurseryRecord::create($this->validatedData($request));

        return response()->json([
            'message' => 'Nursery record saved successfully.',
            'data' => $record,
        ], 201);
    }

    public function update(Request $request, NurseryRecord $nurseryRecord)
    {
        $nurseryRecord->update($this->validatedData($request));

        return response()->json([
            'message' => 'Nursery record updated successfully.',
            'data' => $nurseryRecord->fresh(),
        ]);
    }

    public function destroy(NurseryRecord $nurseryRecord)
    {
        $nurseryRecord->delete();

        return response()->json(['message' => 'Nursery record deleted successfully.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'record_date' => ['required', 'date'],
            'activity' => ['required', 'string', 'max:255'],
            'crop_item' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'nursery_site' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);
    }
}
