<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->unsignedBigInteger('farmer_id')->nullable()->change();
            $table->foreign('farmer_id')
                ->references('id')
                ->on('farmers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('harvests')->whereNull('farmer_id')->delete();

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropForeign(['farmer_id']);
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->unsignedBigInteger('farmer_id')->nullable(false)->change();
            $table->foreign('farmer_id')
                ->references('id')
                ->on('farmers')
                ->cascadeOnDelete();
        });
    }
};
