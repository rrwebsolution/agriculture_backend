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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['IN', 'OUT', 'REVERT']);// IN = Receive, OUT = Distribute
            $table->integer('quantity');
            
            // --- PARA SA RECEIVE STOCK (IN) ---
            $table->string('source_supplier')->nullable(); 

            // --- PARA SA DISTRIBUTE ITEM (OUT) ---
            $table->string('beneficiary_type')->nullable(); // Farmer, Fisherfolk, Cooperative
            $table->string('recipient_name')->nullable();   // Beneficiary Name
            $table->string('rsbsa_no')->nullable();         // RSBSA or FishR No.
            
            $table->date('transaction_date');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
