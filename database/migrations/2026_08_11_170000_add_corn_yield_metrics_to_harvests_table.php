<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->decimal('area_harvested', 10, 2)->nullable()->after('crop_variety');
            $table->decimal('average_yield', 10, 2)->nullable()->after('area_harvested');
            $table->decimal('production', 10, 2)->nullable()->after('average_yield');
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn(['area_harvested', 'average_yield', 'production']);
        });
    }
};
