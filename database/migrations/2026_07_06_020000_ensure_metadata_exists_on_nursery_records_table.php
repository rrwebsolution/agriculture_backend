<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('nursery_records', 'metadata')) {
            return;
        }

        Schema::table('nursery_records', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('nursery_records', 'metadata')) {
            return;
        }

        Schema::table('nursery_records', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
