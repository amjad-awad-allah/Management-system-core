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
        Schema::create('notification_events', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('tenant_id', 64)->default('default')->index();
            $table->string('event_type', 64);              // e.g. 'lesson', 'invoice', 'payment'
            $table->string('event_id', 64);                // Domain entity ULID
            $table->string('notification_type', 64);       // e.g. 'reminder_24h', 'reminder_2h', 'cancelled'
            $table->string('recipient_user_id', 64)->index();
            $table->string('status', 32)->default('generated'); // 'pending', 'generated', 'cancelled'
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('triggered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'event_type', 'event_id', 'notification_type', 'recipient_user_id'],
                'uq_notif_events_idempotency'
            );
            $table->index(['tenant_id', 'recipient_user_id'], 'idx_notif_events_tenant_recipient');
        });

        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('tenant_id', 64)->default('default')->index();
            $table->char('notification_event_id', 26);
            $table->char('correlation_id', 26)->index();   // Distributed tracing & Provider idempotency key
            $table->string('recipient_user_id', 64)->index();
            $table->string('channel', 32);                 // 'in_app', 'reverb', 'whatsapp', 'email'
            $table->json('payload');                       // Schema snapshot: schema_version, template, template_version, locale, data
            $table->string('status', 32)->default('pending')->index(); // 'pending', 'processing', 'sent', 'failed', 'dead'
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('available_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->foreign('notification_event_id')
                ->references('id')
                ->on('notification_events')
                ->onDelete('cascade');

            $table->unique(['notification_event_id', 'channel'], 'uq_notif_outbox_event_channel');
            $table->index(['status', 'available_at'], 'idx_notif_outbox_claim');
            $table->index(['tenant_id', 'recipient_user_id'], 'idx_notif_outbox_tenant_recipient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('notification_events');
    }
};
