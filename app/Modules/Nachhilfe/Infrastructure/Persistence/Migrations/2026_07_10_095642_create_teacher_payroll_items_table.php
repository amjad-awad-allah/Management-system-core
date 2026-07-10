<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payroll_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payroll_id')->constrained('teacher_payrolls')->cascadeOnDelete();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payroll_items');
    }
};
