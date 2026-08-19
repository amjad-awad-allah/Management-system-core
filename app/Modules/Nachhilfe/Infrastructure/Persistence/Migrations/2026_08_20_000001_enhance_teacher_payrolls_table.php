<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_payrolls', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('teacher_payrolls', 'approved_by')) {
                $table->foreignUlid('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('teacher_payrolls', 'snapshot_hash')) {
                $table->string('snapshot_hash', 64)->nullable()->after('approved_by');
            }
        });

        Schema::table('teacher_payroll_items', function (Blueprint $table) {
            $table->unique('lesson_id', 'teacher_payroll_items_lesson_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_payroll_items', function (Blueprint $table) {
            $table->dropUnique('teacher_payroll_items_lesson_id_unique');
        });

        Schema::table('teacher_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_payrolls', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('teacher_payrolls', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('teacher_payrolls', 'snapshot_hash')) {
                $table->dropColumn('snapshot_hash');
            }
        });
    }
};
