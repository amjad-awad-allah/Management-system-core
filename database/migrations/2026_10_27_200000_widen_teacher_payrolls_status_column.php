<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: Per Phase 1B specifications, this migration is prepared for future execution
     * across diverse database engines (MySQL, PostgreSQL) and must NOT be executed immediately.
     */
    public function up(): void
    {
        if (Schema::hasTable('teacher_payrolls') && Schema::hasColumn('teacher_payrolls', 'status')) {
            Schema::table('teacher_payrolls', function (Blueprint $table) {
                $table->string('status', 32)->default('Draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * CRITICAL ACCOUNTING IMPACT AND PRODUCTION NOTICE ON DOWN REVERSIBILITY:
     * 1. Semantic Downgrade: Reverting from widened varchar(32) back to the original enum definition
     *    ['Draft', 'Processing', 'Paid', 'Void'] is non-conservative: 'Approved' records are normalized
     *    to 'Processing' to prevent database-level truncation/enum check constraint crashes on strict engines.
     * 2. Loss of Explicit Approval State: This normalization alters the financial/accounting semantics
     *    because an approved liability awaiting payout becomes an open/in-progress calculation.
     * 3. Production Safety Precondition: THIS down() METHOD MUST NEVER BE EXECUTED IN A PRODUCTION
     *    ENVIRONMENT WITHOUT FIRST:
     *    a) Generating a full database and transactional ledger backup.
     *    b) Auditing and exporting all records currently in 'Approved' status with their snapshot_hash.
     *    c) Formally acknowledging that rolled-back records will require manual review and re-approval.
     */
    public function down(): void
    {
        if (Schema::hasTable('teacher_payrolls') && Schema::hasColumn('teacher_payrolls', 'status')) {
            // 1. Explicit audit check of affected records prior to column alteration
            $affectedRecords = \Illuminate\Support\Facades\DB::table('teacher_payrolls')
                ->whereIn('status', ['Approved', 'approved', 'APPROVED'])
                ->get(['id', 'month', 'teacher_id', 'total_amount', 'snapshot_hash']);

            $affectedCount = $affectedRecords->count();

            if ($affectedCount > 0) {
                \Illuminate\Support\Facades\Log::warning(
                    "Accounting Alert on Migration down(): {$affectedCount} 'Approved' teacher payroll record(s) are being downgraded to 'Processing'. Accounting state is NOT equivalent to original approval."
                );

                // Strict accounting enforcement is ENABLED by default: reject migration down if approved records exist
                if (!env('ALLOW_UNSAFE_PAYROLL_ROLLBACK', false)) {
                    throw new \DomainException(
                        "Cannot revert migration down: Found {$affectedCount} record(s) in 'Approved' status. " .
                        "Reversion would destroy approved accounting state by downgrading to 'Processing'. " .
                        "To force reversion, backup records and set ALLOW_UNSAFE_PAYROLL_ROLLBACK=true in environment."
                    );
                }

                // 2. Explicitly allowed by administrator: normalize to avoid database engine truncation
                \Illuminate\Support\Facades\DB::table('teacher_payrolls')
                    ->whereIn('status', ['Approved', 'approved', 'APPROVED'])
                    ->update(['status' => 'Processing']);
            }

            // 3. Revert column definition to original initial enum
            Schema::table('teacher_payrolls', function (Blueprint $table) {
                $table->enum('status', ['Draft', 'Processing', 'Paid', 'Void'])->default('Draft')->change();
            });
        }
    }
};
