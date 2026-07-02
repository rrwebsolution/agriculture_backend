<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_records', function (Blueprint $table) {
            $table->id();
            $table->date('record_date');
            $table->string('activity');
            $table->string('crop_item');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->string('nursery_site')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['record_date', 'activity']);
            $table->index('crop_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_records');
    }
};
