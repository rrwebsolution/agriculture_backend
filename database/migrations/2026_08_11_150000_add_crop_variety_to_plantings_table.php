<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantings', function (Blueprint $table) {
            $table->string('crop_variety', 100)->nullable()->after('crop_id');
        });
    }

    public function down(): void
    {
        Schema::table('plantings', function (Blueprint $table) {
            $table->dropColumn('crop_variety');
        });
    }
};
