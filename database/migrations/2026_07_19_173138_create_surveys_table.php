<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('channel_id')->index();
            $table->ulid('created_by')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('chat_channels')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
