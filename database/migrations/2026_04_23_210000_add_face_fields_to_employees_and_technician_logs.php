<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->longText('face_reference_image')->nullable()->after('current_assignment');
        });

        Schema::table('technician_logs', function (Blueprint $table) {
            $table->boolean('face_verified')->default(false)->after('notes');
            $table->timestamp('face_verified_at')->nullable()->after('face_verified');
            $table->decimal('face_match_score', 5, 2)->nullable()->after('face_verified_at');
            $table->longText('verification_photo')->nullable()->after('face_match_score');
        });
    }

    public function down(): void
    {
        Schema::table('technician_logs', function (Blueprint $table) {
            $table->dropColumn([
                'face_verified',
                'face_verified_at',
                'face_match_score',
                'verification_photo',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('face_reference_image');
        });
    }
};
