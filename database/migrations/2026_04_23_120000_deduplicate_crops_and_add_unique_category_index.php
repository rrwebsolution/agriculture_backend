<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::table('crops')
            ->select('category', DB::raw('MIN(id) as keeper_id'))
            ->groupBy('category')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $duplicateIds = DB::table('crops')
                ->where('category', $group->category)
                ->where('id', '!=', $group->keeper_id)
                ->pluck('id');

            if ($duplicateIds->isEmpty()) {
                continue;
            }

            DB::table('farmers')
                ->whereIn('crop_id', $duplicateIds)
                ->update(['crop_id' => $group->keeper_id]);

            DB::table('plantings')
                ->whereIn('crop_id', $duplicateIds)
                ->update(['crop_id' => $group->keeper_id]);

            DB::table('harvests')
                ->whereIn('crop_id', $duplicateIds)
                ->update(['crop_id' => $group->keeper_id]);

            DB::table('crops')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        Schema::table('crops', function (Blueprint $table) {
            $table->unique('category', 'crops_category_unique');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropUnique('crops_category_unique');
        });
    }
};
