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
        Schema::dropIfExists('invoice_items');
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('invoice_id')->index();
            $table->ulid('lesson_id')->index();
            $table->string('description');
            $table->decimal('hours', 8, 2);
            $table->decimal('rate', 8, 2);
            $table->decimal('amount', 8, 2);
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
