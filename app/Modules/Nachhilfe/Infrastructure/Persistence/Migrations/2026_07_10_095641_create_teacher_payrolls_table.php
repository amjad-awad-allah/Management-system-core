<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payrolls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('month'); // e.g., '2026-07'
            $table->integer('total_completed_lessons')->default(0);
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['Draft', 'Processing', 'Paid', 'Void'])->default('Draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payrolls');
    }
};
