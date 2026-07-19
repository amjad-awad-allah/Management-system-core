<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('notification_group_id')->index();
            $table->ulid('user_id'); // Recipient user
            $table->string('recipient_type')->nullable();
            $table->ulid('recipient_id')->nullable();
            $table->string('delivery_channel', 50);
            $table->string('type', 100);
            $table->string('source_type')->nullable();
            $table->ulid('source_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('external_id')->nullable();
            $table->string('priority')->default('normal');
            $table->string('delivery_status')->default('pending');
            $table->string('period_key', 20)->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'source_id', 'type', 'period_key', 'delivery_channel'], 'notifications_unique_delivery');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
