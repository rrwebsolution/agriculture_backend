<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', [
                'Production',
                'Fishery',
                'Livestock & Poultry',
                'Financial',
                'Census',
                'Inventory',
            ]);
            $table->string('module');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('generated_by');
            $table->timestamp('generated_at')->useCurrent();
            $table->enum('format', ['PDF', 'XLSX'])->default('PDF');
            $table->enum('status', ['Published', 'Pending Review', 'Draft'])->default('Pending Review');
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable(); // stored path in storage/app/reports/
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
