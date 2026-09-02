<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemLabel\BulkUpdateSystemLabelsRequest;
use App\Models\SystemLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemLabelController extends Controller
{
    private const CACHE_KEY = 'system_labels_map';

    /**
     * Flat key => effective value map. Any authenticated user can read this,
     * since Sidebar/Dashboard/etc. render it for every user, not just admins.
     */
    public function index(): JsonResponse
    {
        $map = Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            return SystemLabel::all()
                ->mapWithKeys(fn (SystemLabel $label) => [$label->key => $label->effectiveValue()]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $map,
        ], 200);
    }

    /**
     * Full records grouped by `group`, for the Super Admin management table.
     */
    public function manage(): JsonResponse
    {
        $grouped = SystemLabel::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return response()->json([
            'status' => 'success',
            'data' => $grouped,
        ], 200);
    }

    public function bulkUpdate(BulkUpdateSystemLabelsRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated('labels') as $entry) {
                SystemLabel::where('key', $entry['key'])->update([
                    'value' => $entry['value'] ?? null,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        Cache::forget(self::CACHE_KEY);

        return response()->json([
            'status' => 'success',
            'message' => 'Labels updated successfully',
            'data' => SystemLabel::orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ], 200);
    }

    public function reset(SystemLabel $label): JsonResponse
    {
        $label->update([
            'value' => null,
            'updated_by' => request()->user()->id,
        ]);

        Cache::forget(self::CACHE_KEY);

        return response()->json([
            'status' => 'success',
            'message' => 'Label reset to default',
            'data' => $label,
        ], 200);
    }
}
