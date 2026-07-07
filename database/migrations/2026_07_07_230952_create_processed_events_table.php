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
        Schema::create('processed_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_id')->index();
            $table->string('consumer_name')->index();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['event_id', 'consumer_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_events');
    }
};
