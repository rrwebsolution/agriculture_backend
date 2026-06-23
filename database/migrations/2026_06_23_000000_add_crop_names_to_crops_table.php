<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->text('crop_names')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('crop_names');
        });
    }
};
