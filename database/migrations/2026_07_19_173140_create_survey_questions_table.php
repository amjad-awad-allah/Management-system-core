<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('survey_id')->index();
            $table->text('question');
            $table->enum('type', ['text', 'single_choice', 'multiple_choice', 'rating'])->default('text');
            $table->json('options')->nullable(); // for choice-based questions
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
