<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Triple-Lock Indexes (منع التعارض)
            $table->index(['teacher_id', 'date', 'start_time', 'end_time'], 'idx_lessons_teacher_time');
            $table->index(['room_id', 'date', 'start_time', 'end_time'], 'idx_lessons_room_time');
            
            // Query Indexes
            $table->index(['date', 'status'], 'idx_lessons_date_status');
            $table->index('schedule_template_id');
        });
        
        Schema::table('lesson_students', function (Blueprint $table) {
            $table->index(['lesson_id', 'student_id']);
            $table->index('package_id');
        });
        
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['lesson_student_id', 'status']);
            $table->index('marked_at');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('idx_lessons_teacher_time');
            $table->dropIndex('idx_lessons_room_time');
            $table->dropIndex('idx_lessons_date_status');
        });
    }
};
