<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->onDelete('cascade');
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade'); 
            $table->foreignId('crop_id')->constrained()->onDelete('cascade');
            $table->date('dateHarvested');
            $table->string('quantity');
            $table->string('quality');  
            $table->string('value')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('harvests');
    }
};