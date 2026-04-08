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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique(); // e.g., EXP-2024-0001
            $table->string('item');
            $table->string('category'); // From your SearchableSelect
            $table->string('project');  // From your SearchableSelect (Rice Program, etc.)
            $table->decimal('amount', 15, 2);
            $table->date('date_incurred');
            $table->string('status')->default('Pending'); // Paid, Pending, etc.
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Optional: if you want to recover deleted records
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
