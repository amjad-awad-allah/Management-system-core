<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_id')->constrained('student_packages')->cascadeOnDelete();
            $table->foreignUlid('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->enum('type', ['Deduction', 'Refund', 'Adjustment']);
            $table->decimal('hours', 8, 2);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usages');
    }
};
