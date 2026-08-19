<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_payrolls') && !Schema::hasColumn('teacher_payrolls', 'snapshot_hash_version')) {
            Schema::table('teacher_payrolls', function (Blueprint $table) {
                $table->unsignedTinyInteger('snapshot_hash_version')->default(1)->after('snapshot_hash');
            });
        }

        if (!Schema::hasTable('pdf_export_logs')) {
            Schema::create('pdf_export_logs', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('report_type');
                $table->string('entity_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->boolean('success')->default(true);
                $table->text('failure_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_export_logs');

        if (Schema::hasTable('teacher_payrolls') && Schema::hasColumn('teacher_payrolls', 'snapshot_hash_version')) {
            Schema::table('teacher_payrolls', function (Blueprint $table) {
                $table->dropColumn('snapshot_hash_version');
            });
        }
    }
};
