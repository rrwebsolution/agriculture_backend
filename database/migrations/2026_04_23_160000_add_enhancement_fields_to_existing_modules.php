<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fishery_records', function (Blueprint $table) {
            $table->decimal('hours_spent_fishing', 8, 2)->nullable()->after('market_value');
            $table->json('vessel_catch_entries')->nullable()->after('hours_spent_fishing');
        });

        Schema::table('farmers', function (Blueprint $table) {
            $table->string('soil_type')->nullable()->after('farm_coordinates');
            $table->string('gpx_file_path')->nullable()->after('soil_type');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->json('filters')->nullable()->after('notes');
            $table->json('selected_fields')->nullable()->after('filters');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['filters', 'selected_fields']);
        });

        Schema::table('farmers', function (Blueprint $table) {
            $table->dropColumn(['soil_type', 'gpx_file_path']);
        });

        Schema::table('fishery_records', function (Blueprint $table) {
            $table->dropColumn(['hours_spent_fishing', 'vessel_catch_entries']);
        });
    }
};
