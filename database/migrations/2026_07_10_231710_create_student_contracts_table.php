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
        Schema::create('student_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id')->index();
            $table->ulid('subject_id')->index();
            $table->integer('hours_per_week');
            $table->decimal('hourly_rate', 8, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active, cancelled, expired
            $table->timestamps();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_contracts');
    }
};
