<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('channel_id')->index();
            $table->ulid('user_id')->index();
            $table->enum('role', ['member', 'admin', 'observer'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'user_id']);
            $table->foreign('channel_id')->references('id')->on('chat_channels')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
