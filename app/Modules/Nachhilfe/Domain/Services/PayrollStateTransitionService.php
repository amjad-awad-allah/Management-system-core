<?php

namespace App\Modules\Nachhilfe\Domain\Services;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayrollStateTransitionService
{
    /**
     * Atomically transition a teacher payroll to a target status with pessimistic locking,
     * accounting integrity verification, and permanent audit logging.
     *
     * @param TeacherPayroll $payroll
     * @param string $targetStatus
     * @param User|null $actor
     * @param array $options [
     *     'total_amount' => float|null,
     *     'void_reason' => string|null,
     *     'ip_address' => string|null,
     *     'user_agent' => string|null,
     * ]
     * @return TeacherPayroll
     *
     * @throws \DomainException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function transition(
        TeacherPayroll $payroll,
        string $targetStatus,
        ?User $actor = null,
        array $options = []
    ): TeacherPayroll {
        $targetStatusLower = strtolower($targetStatus);

        // Enforce general billing authorization for all payroll state transitions
        if (!$actor || (!$actor->hasRole(['Super Admin', 'Center Manager']) && !$actor->can('manage billing'))) {
            abort(403, 'Unauthorized to transition payroll status.');
        }

        return DB::transaction(function () use ($payroll, $targetStatus, $targetStatusLower, $actor, $options) {
            // 1. Acquire pessimistic row lock
            /** @var TeacherPayroll $lockedPayroll */
            $lockedPayroll = TeacherPayroll::where('id', $payroll->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Strict state machine check on locked instance
            if (!$lockedPayroll->canTransitionTo($targetStatus)) {
                throw new \DomainException(
                    "Invalid status transition from {$lockedPayroll->status} to {$targetStatus}."
                );
            }

            // 3. Paid records represent disbursed funds and can NEVER be voided
            if ($targetStatusLower === 'void' && strtolower((string) $lockedPayroll->status) === 'paid') {
                throw new \DomainException(
                    'Cannot void a paid payroll: financial disbursement has already occurred.'
                );
            }

            // 4. Voiding an approved/sealed record requires elevated authority and mandatory reason
            if ($targetStatusLower === 'void' && $lockedPayroll->isSealedOrApproved()) {
                if (!$actor || !$actor->hasRole(['Super Admin', 'Center Manager'])) {
                    abort(403, 'Unauthorized: Only Super Admin or Center Manager can void an approved payroll.');
                }

                $voidReason = trim((string) ($options['void_reason'] ?? ''));
                if (empty($voidReason)) {
                    throw new \DomainException(
                        'A valid void_reason is strictly required when voiding an approved payroll.'
                    );
                }
            }

            // 5. Transitioning to 'Paid' strictly requires verified snapshot seal
            if ($targetStatusLower === 'paid' && !$lockedPayroll->hasValidSeal()) {
                throw new \DomainException(
                    'Cannot mark payroll as Paid: record has no valid sealed snapshot hash.'
                );
            }

            // 6. Handle financial figure updates if supplied
            if (array_key_exists('total_amount', $options) && $options['total_amount'] !== null) {
                $newAmount = round((float) $options['total_amount'], 2);
                if ($lockedPayroll->isSealedOrApproved()) {
                    if (abs($newAmount - (float) $lockedPayroll->total_amount) > 0.001) {
                        throw new \DomainException(
                            'Cannot modify financial figures of an approved, paid, or void payroll.'
                        );
                    }
                } else {
                    if ($lockedPayroll->items()->exists()) {
                        $itemsSum = round((float) $lockedPayroll->items()->sum('amount'), 2);
                        if (abs($newAmount - $itemsSum) > 0.001) {
                            throw new \DomainException(
                                'Cannot set total_amount to a value that does not match the sum of payroll items.'
                            );
                        }
                    }
                    $lockedPayroll->total_amount = $newAmount;
                }
            }

            // 7. Snapshot original state before mutating
            $originalStatus = (string) $lockedPayroll->status;
            $originalHash = (string) $lockedPayroll->snapshot_hash;
            $originalAmount = (string) $lockedPayroll->total_amount;
            $wasSealedOrApproved = $lockedPayroll->isSealedOrApproved();

            // 8. Apply status and save atomically
            $lockedPayroll->status = $targetStatus;
            $lockedPayroll->save();

            // 9. Insert permanent audit log atomically inside same transaction
            if ($targetStatusLower === 'void' && $wasSealedOrApproved) {
                $reason = (string) ($options['void_reason'] ?? '');
                $userId = $actor?->id;

                Log::warning(
                    "Approved payroll {$lockedPayroll->id} for month {$lockedPayroll->month} voided by user {$userId}. Reason: {$reason}"
                );

                DB::table('audit_logs')->insert([
                    'id' => (string) Str::ulid(),
                    'user_id' => $userId,
                    'event' => 'payroll_voided',
                    'auditable_type' => get_class($lockedPayroll),
                    'auditable_id' => $lockedPayroll->id,
                    'old_values' => json_encode([
                        'status' => $originalStatus,
                        'snapshot_hash' => $originalHash,
                        'total_amount' => $originalAmount,
                    ]),
                    'new_values' => json_encode([
                        'status' => 'Void',
                        'void_reason' => $reason,
                    ]),
                    'ip_address' => $options['ip_address'] ?? null,
                    'user_agent' => $options['user_agent'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (strtolower($originalStatus) !== $targetStatusLower) {
                DB::table('audit_logs')->insert([
                    'id' => (string) Str::ulid(),
                    'user_id' => $actor?->id,
                    'event' => 'payroll_status_updated',
                    'auditable_type' => get_class($lockedPayroll),
                    'auditable_id' => $lockedPayroll->id,
                    'old_values' => json_encode([
                        'status' => $originalStatus,
                        'total_amount' => $originalAmount,
                    ]),
                    'new_values' => json_encode([
                        'status' => $lockedPayroll->status,
                        'total_amount' => (string) $lockedPayroll->total_amount,
                    ]),
                    'ip_address' => $options['ip_address'] ?? null,
                    'user_agent' => $options['user_agent'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $lockedPayroll;
        });
    }
}
