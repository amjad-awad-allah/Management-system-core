<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('channel_id')->index();
            $table->ulid('sender_id')->index();
            $table->text('body')->nullable();
            $table->enum('type', ['text', 'file', 'image', 'system', 'survey_response'])->default('text');
            // metadata: { file_path, file_name, file_size, mime_type, survey_id }
            $table->json('metadata')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('chat_channels')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
