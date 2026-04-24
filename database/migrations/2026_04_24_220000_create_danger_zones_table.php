<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danger_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('zone_type');
            $table->text('description')->nullable();
            $table->string('status')->default('Active');
            $table->string('color', 20)->default('#dc2626');
            $table->string('fill_color', 20)->default('#f87171');
            $table->json('positions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danger_zones');
    }
};
