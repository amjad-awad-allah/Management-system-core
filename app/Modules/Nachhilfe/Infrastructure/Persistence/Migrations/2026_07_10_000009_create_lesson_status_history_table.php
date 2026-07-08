<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_status_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            
            $table->enum('old_status', ['scheduled', 'completed', 'cancelled', 'rescheduled']);
            $table->enum('new_status', ['scheduled', 'completed', 'cancelled', 'rescheduled']);
            
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_status_history');
    }
};
