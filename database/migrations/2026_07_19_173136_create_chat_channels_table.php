<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('type', ['direct', 'group', 'survey'])->default('direct');
            $table->string('name')->nullable();
            $table->ulid('created_by')->index();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_channels');
    }
};
