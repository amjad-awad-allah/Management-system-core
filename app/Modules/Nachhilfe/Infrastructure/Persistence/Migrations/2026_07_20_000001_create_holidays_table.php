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
        Schema::create('holidays', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('source')->default('custom'); // external, custom, override
            $table->string('external_id')->nullable();
            $table->string('type')->default('public'); // public, school, center
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('state')->nullable(); // e.g. NW, BY or null for national/center
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['state', 'is_active']);
            $table->unique(['source', 'external_id', 'state'], 'holidays_source_ext_state_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
