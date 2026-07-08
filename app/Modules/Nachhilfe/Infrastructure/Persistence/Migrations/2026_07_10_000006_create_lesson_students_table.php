<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('package_id')->nullable()->constrained('packages')->nullOnDelete();
            
            $table->decimal('hours_consumed', 5, 2)->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // منع تسجيل نفس الطالب مرتين في نفس الدرس
            $table->unique(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_students');
    }
};
