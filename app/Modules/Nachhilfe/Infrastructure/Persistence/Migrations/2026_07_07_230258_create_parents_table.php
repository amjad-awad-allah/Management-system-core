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
        Schema::create('parents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->index();
            $table->string('name');
            $table->text('phone')->nullable(); // Encrypted
            $table->string('phone_bidx')->nullable()->index(); // Blind Index for Exact Search
            $table->text('email')->nullable(); // Encrypted
            $table->string('email_bidx')->nullable()->index(); // Blind Index for Exact Search
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
