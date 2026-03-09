<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            
            // --- IDENTITY & PERSONAL INFO ---
            $table->string('system_id')->unique(); // e.g., FRM-2026-XXXXXX
            $table->string('rsbsa_no')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->date('dob');
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade'); // Residence
            $table->string('address_details')->nullable();
            $table->string('contact_no')->nullable();
            
            // --- FARM PROFILE & TECHNICAL DETAILS ---
            $table->foreignId('farm_barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->string('farm_sitio')->nullable();
            $table->foreignId('crop_id')->constrained('crops')->onDelete('cascade'); // Main Crop
            $table->string('ownership_type'); // Owner, Tenant, Lease
            $table->decimal('total_area', 10, 4); // In Hectares
            $table->string('topography'); // Plain, Rolling, Sloping
            $table->string('irrigation_type'); // Irrigated, Rainfed, Upland
            $table->text('area_breakdown')->nullable(); // Detailed info per crop
            
            // --- AFFILIATIONS ---
            $table->boolean('is_main_livelihood')->default(true);
            $table->boolean('is_coop_member')->default(false);
            // Foreign key to cooperatives table
            $table->foreignId('cooperative_id')->nullable()->constrained('cooperatives')->onDelete('set null');
            
            // --- LGU PROGRAMS / ASSISTANCE (OPTIONAL) ---
            $table->string('program_name')->nullable();
            $table->string('assistance_type')->nullable(); // Seeds, Fertilizer, etc.
            $table->date('date_released')->nullable();
            $table->string('quantity')->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->string('funding_source')->nullable();
            
            // --- STATUS ---
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};