<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void 
    {
        Schema::create('fishery_records', function (Blueprint $table) {
            $table->id();
            $table->string('fishr_id'); // Link to Fisherfolk System ID
            $table->string('name');
            $table->string('gender');
            $table->string('contact_no')->nullable();
            $table->string('boat_name')->nullable();
            $table->string('gear_type');
            $table->string('fishing_area');
            $table->string('catch_species');
            
            // ⚖️ Catch Weight (kg)
            $table->decimal('yield', 10, 2); 

            // 💰 Market Value / Sales (₱) - MERGED
            $table->decimal('market_value', 15, 2)->default(0.00); 

            $table->date('date'); // Catch Date
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fishery_records');
    }
};