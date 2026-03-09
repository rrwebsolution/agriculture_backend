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
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->string('system_id')->unique();
            $table->string('cda_no')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('chairman');
            $table->string('contact_no')->nullable();
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->text('address_details')->nullable();
            $table->integer('member_count')->default(0);
            $table->decimal('capital_cbu', 15, 2)->default(0.00);
            $table->string('status')->default('Compliant');
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
