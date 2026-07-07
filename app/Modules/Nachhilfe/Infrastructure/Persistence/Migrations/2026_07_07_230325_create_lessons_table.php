<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->index();
            $table->foreignUlid('teacher_id')->index();
            $table->foreignUlid('subject_id')->index();
            $table->timestamp('scheduled_at')->index();
            $table->integer('duration_minutes');
            $table->string('status')->index();
            $table->decimal('price', 8, 2);
            $table->softDeletes()->index();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
