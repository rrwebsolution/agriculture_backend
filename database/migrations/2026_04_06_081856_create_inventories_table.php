<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commodity')->nullable();
            $table->string('category');
            $table->string('sku')->unique();
            $table->string('batch')->nullable();
            $table->integer('stock')->default(0);
            $table->string('unit');
            $table->integer('threshold')->default(10);
            $table->string('status')->default('In Stock'); // In Stock, Low Stock, Out of Stock
            
            // --- GIDUGANG NGA FIELDS ---
            $table->integer('recipients')->default(0);
            $table->string('year')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};