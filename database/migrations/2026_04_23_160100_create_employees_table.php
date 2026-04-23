<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('contact_no')->nullable();
            $table->string('position');
            $table->string('department')->default('City Agriculture Office');
            $table->string('division')->nullable();
            $table->string('employment_type')->default('Regular');
            $table->enum('status', ['Active', 'Inactive', 'On Leave'])->default('Active');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('work_location')->nullable();
            $table->string('current_assignment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
