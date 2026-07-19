<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('survey_id')->index();
            $table->ulid('user_id')->index();
            // answers: { "question_ulid": "answer_value", ... }
            $table->json('answers');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['survey_id', 'user_id']); // one response per user
            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
