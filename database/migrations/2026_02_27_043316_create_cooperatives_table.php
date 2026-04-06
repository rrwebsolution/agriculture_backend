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
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->string('system_id')->unique();
            $table->string('cda_no')->unique(); // Used as Registration No.
            $table->string('name');
            
            // Merged from the update migration
            $table->string('registration')->nullable(); // e.g., DOLE, SEC, CDA
            $table->string('org_type')->nullable();     // e.g., Association, Cooperative
            
            $table->string('type');
            $table->string('chairman');
            $table->string('contact_no')->nullable();
            
            // Relationship
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            
            $table->text('address_details')->nullable();
            $table->decimal('capital_cbu', 15, 2)->default(0.00);
            $table->string('status')->default('Compliant');
            
            // Note: member_count is removed as per your second migration request
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperatives');
    }
};