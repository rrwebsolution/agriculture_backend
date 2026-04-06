<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            
            // --- PERSONAL INFO ---
            $table->string('system_id')->unique();
            $table->string('rsbsa_no')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->date('dob');
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->string('address_details')->nullable();
            $table->string('contact_no')->nullable();
            
            // 🌟 MULTIPLE FARMS ARRAY
            $table->json('farms_list')->nullable();
            
            // --- SINGLE / LEGACY FARM DETAILS ---
            $table->foreignId('farm_barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->string('farm_sitio')->nullable();
            $table->foreignId('crop_id')->constrained('crops')->onDelete('cascade');
            $table->string('ownership_type');
            $table->decimal('total_area', 10, 4);
            
            // 🌟 FARM COORDINATES ARRAY (For Maps)
            $table->json('farm_coordinates')->nullable();
            
            $table->string('topography');
            $table->string('irrigation_type');
            $table->text('area_breakdown')->nullable();
            
            // --- AFFILIATIONS ---
            $table->boolean('is_main_livelihood')->default(true);
            $table->boolean('is_coop_member')->default(false);
            
            // 🌟 MULTIPLE COOPERATIVES ARRAY (Diritso na nga JSON, walay foreign key constraint!)
            $table->json('cooperative_id')->nullable(); 
            
            // 🌟 MULTIPLE ASSISTANCE PROGRAMS ARRAY
            $table->json('assistances_list')->nullable();
            
            // --- SINGLE / LEGACY ASSISTANCE PROGRAMS ---
            $table->string('program_name')->nullable();
            $table->string('assistance_type')->nullable();
            $table->date('date_released')->nullable();
            $table->string('quantity')->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->string('funding_source')->nullable();
            
            // --- STATUS ---
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};