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
        // 1. Main Equipments Table
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();       // Serial Number / SKU
            $table->string('name');                // Equipment Name
            $table->string('type');                // e.g., Farm Machinery, Fishery
            $table->string('program')->nullable(); // e.g., Rice Program
            $table->string('condition');           // Excellent, Good, Fair, etc.
            $table->string('status');              // Deployed, In Depot, etc.
            $table->date('last_check')->nullable();
            $table->timestamps();
        });

        // 2. Pivot Table (Many-to-Many)
        Schema::create('equipment_cooperative', function (Blueprint $table) {
            $table->id();
            
            // FIX: Explicitly reference the 'equipments' table
            $table->foreignId('equipment_id')
                  ->constrained('equipments') 
                  ->onDelete('cascade');

            // Explicitly reference the 'cooperatives' table
            $table->foreignId('cooperative_id')
                  ->constrained('cooperatives')
                  ->onDelete('cascade');
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_cooperative');
        Schema::dropIfExists('equipments');
    }
};