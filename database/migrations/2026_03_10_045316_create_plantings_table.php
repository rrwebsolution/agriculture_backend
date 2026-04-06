<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plantings', function (Blueprint $table) {
            $table->id();
            
            // 🌟 Updated to barangay_id
            $table->foreignId('farmer_id')->constrained('farmers')->onDelete('cascade');
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->foreignId('crop_id')->constrained('crops')->onDelete('cascade');
            
            $table->decimal('area', 8, 4); 
            $table->date('date_planted');
            $table->date('est_harvest');
            $table->string('status')->default('Seedling');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plantings');
    }
};