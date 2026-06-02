<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (!Schema::hasColumn('farmers', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('suffix');
            }
        });

        Schema::table('fisherfolks', function (Blueprint $table) {
            if (!Schema::hasColumn('fisherfolks', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('suffix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            if (Schema::hasColumn('farmers', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });

        Schema::table('fisherfolks', function (Blueprint $table) {
            if (Schema::hasColumn('fisherfolks', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });
    }
};
