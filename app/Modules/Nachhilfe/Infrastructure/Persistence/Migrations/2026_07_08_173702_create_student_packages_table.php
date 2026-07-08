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
        Schema::create('student_packages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('package_id')->nullable();
            $table->ulid('subject_id')->nullable(); // Null means open for all subjects
            
            $table->string('funding_source')->default('private'); // 'private', 'jobcenter'
            $table->string('voucher_reference')->nullable(); // Jobcenter BuT reference number
            
            $table->integer('total_hours');
            $table->integer('remaining_hours');
            $table->string('status')->default('active'); // active, exhausted, expired
            
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_packages');
    }
};
