<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->boolean('is_farmer')->default(true)->after('area_breakdown');
            $table->boolean('is_farm_worker')->default(false)->after('is_farmer');

            $table->unsignedBigInteger('farm_barangay_id')->nullable()->change();
            $table->unsignedBigInteger('crop_id')->nullable()->change();
            $table->string('ownership_type')->nullable()->change();
            $table->decimal('total_area', 10, 4)->nullable()->change();
            $table->string('topography')->nullable()->change();
            $table->string('irrigation_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropColumn(['is_farmer', 'is_farm_worker']);
        });
    }
};
