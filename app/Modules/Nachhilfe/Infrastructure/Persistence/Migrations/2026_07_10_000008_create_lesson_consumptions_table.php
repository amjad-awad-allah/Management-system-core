<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_consumptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_student_id')->constrained('lesson_students')->cascadeOnDelete();
            $table->foreignUlid('package_id')->constrained('packages')->cascadeOnDelete();
            
            $table->decimal('hours_used', 5, 2);
            $table->enum('consumption_type', ['attendance', 'cancellation_fee', 'manual_adjustment']);
            
            $table->decimal('balance_before', 5, 2);
            $table->decimal('balance_after', 5, 2);
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_consumptions');
    }
};
