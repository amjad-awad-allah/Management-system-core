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
        Schema::dropIfExists('invoices');
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->ulid('student_id')->index();
            $table->string('month'); // Format: YYYY-MM
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('draft'); // draft, unpaid, paid, void
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
