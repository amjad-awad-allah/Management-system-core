<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignUlid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUlid('subject_id')->constrained('subjects')->cascadeOnDelete();
            
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->integer('interval')->default(1); // every 1 week, every 2 weeks
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('days_of_week'); // ["monday", "wednesday"]
            
            $table->time('start_time');
            $table->time('end_time');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_templates');
    }
};
