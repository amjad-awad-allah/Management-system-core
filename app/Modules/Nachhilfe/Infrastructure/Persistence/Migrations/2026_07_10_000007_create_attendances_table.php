<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_student_id')->constrained('lesson_students')->cascadeOnDelete();
            
            $table->enum('status', [
                'present', 
                'absent_excused', 
                'absent_unexcused', 
                'late'
            ])->default('present');
            
            $table->foreignUlid('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->useCurrent();
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            // منع تسجيل الحضور مرتين لنفس الطالب في نفس الدرس
            $table->unique('lesson_student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
