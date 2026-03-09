<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fisherfolks', function (Blueprint $table) {
            $table->id();
            $table->string('system_id')->unique();
            
            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('gender');
            $table->date('dob');
            $table->integer('age')->nullable();
            $table->string('civil_status');
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->text('address_details');
            $table->string('contact_no')->nullable();
            $table->string('education')->nullable();

            // Fishery Profile
            $table->string('fisher_type'); // Municipal, Commercial, etc.
            $table->boolean('is_main_livelihood')->default(false);
            $table->integer('years_in_fishing')->nullable();
            $table->boolean('org_member')->default(false);
            $table->string('org_name')->nullable();

            // Boat & Gear Details
            $table->string('boat_name')->nullable();
            $table->string('boat_type')->nullable(); // Motorized, Non-Motorized
            $table->string('engine_hp')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('gear_type')->nullable();
            $table->integer('gear_units')->nullable();
            $table->string('fishing_area')->nullable();

            // Aquaculture (Optional for Operators)
            $table->string('farm_name')->nullable();
            $table->string('farm_owner')->nullable();
            $table->string('farm_location')->nullable();
            $table->string('farm_type')->nullable(); // Fish Pond, Cage, etc.
            $table->string('farm_size')->nullable();
            $table->string('species_cultured')->nullable();

            // Compliance & Permits
            $table->string('permit_no')->nullable();
            $table->date('permit_date_issued')->nullable();
            $table->date('permit_expiry')->nullable();
            $table->string('inspection_status')->default('Pending');

            // Assistance Program (Optional)
            $table->string('beneficiary_program')->nullable();
            $table->string('assistance_type')->nullable();
            $table->date('date_released')->nullable();
            $table->string('quantity')->nullable();
            $table->string('funding_source')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fisherfolks');
    }
};