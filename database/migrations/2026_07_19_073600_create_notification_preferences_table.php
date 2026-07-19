<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->index();
            $table->string('notification_type');
            $table->string('channel');
            $table->boolean('enabled')->default(true);
            $table->string('quiet_hours_start')->nullable();
            $table->string('quiet_hours_end')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'notification_type', 'channel'], 'pref_user_type_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
