<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherPayroll extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'snapshot_hash_version' => 'integer',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Core\Models\User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(TeacherPayrollItem::class, 'payroll_id');
    }

    /**
     * Create a new Eloquent query builder that intercepts Eloquent mass updates and deletions of sealed records.
     */
    public function newEloquentBuilder($query)
    {
        return new class($query) extends \Illuminate\Database\Eloquent\Builder {
            public function update(array $values)
            {
                $records = (clone $this)->get();

                foreach ($records as $record) {
                    /** @var TeacherPayroll $record */
                    $origStatus = (string) $record->status;
                    $origHash = $record->snapshot_hash;
                    $origSealed = $record->isSealedOrApproved();

                    if (array_key_exists('snapshot_hash', $values)) {
                        $newHash = $values['snapshot_hash'];
                        if (!empty($origHash) && $newHash !== $origHash) {
                            throw new \DomainException('Cannot tamper with or modify an existing snapshot_hash.');
                        }
                    }

                    // Protect approval metadata once set on a sealed/approved record
                    if (array_key_exists('approved_at', $values) && $origSealed && $record->approved_at !== null) {
                        $origIso = \Carbon\Carbon::parse($record->approved_at)->utc()->toIso8601String();
                        $newIso = $values['approved_at'] !== null
                            ? \Carbon\Carbon::parse($values['approved_at'])->utc()->toIso8601String()
                            : '';
                        if ($origIso !== $newIso) {
                            throw new \DomainException("Cannot modify approval metadata 'approved_at' of an approved, paid, or void payroll.");
                        }
                    }

                    if (array_key_exists('approved_by', $values) && $origSealed && $record->approved_by !== null) {
                        if ((string) $record->approved_by !== (string) $values['approved_by']) {
                            throw new \DomainException("Cannot modify approval metadata 'approved_by' of an approved, paid, or void payroll.");
                        }
                    }

                    if (array_key_exists('snapshot_hash_version', $values) && !empty($origHash)) {
                        if ((int) ($record->snapshot_hash_version ?: 1) !== (int) ($values['snapshot_hash_version'] ?: 1)) {
                            throw new \DomainException("Cannot modify approval metadata 'snapshot_hash_version' of a sealed payroll.");
                        }
                    }

                    $financialFields = ['total_amount', 'total_hours', 'total_completed_lessons', 'teacher_id', 'month'];
                    foreach ($financialFields as $field) {
                        if (array_key_exists($field, $values) && $origSealed) {
                            $origVal = $record->$field;
                            $newVal = $values[$field];
                            if (is_numeric($origVal) && is_numeric($newVal)) {
                                if (abs((float) $origVal - (float) $newVal) > 0.001) {
                                    throw new \DomainException("Cannot modify financial figure '{$field}' of an approved, paid, or void payroll.");
                                }
                            } elseif ((string) $origVal !== (string) $newVal) {
                                throw new \DomainException("Cannot modify accounting attribute '{$field}' of an approved, paid, or void payroll.");
                            }
                        }
                    }

                    if (array_key_exists('status', $values)) {
                        $newStatus = (string) $values['status'];
                        $evalModel = clone $record;
                        foreach ($values as $k => $v) {
                            if ($k !== 'status') {
                                $evalModel->$k = $v;
                            }
                        }
                        $evalModel->status = $origStatus;

                        if (!$evalModel->canTransitionTo($newStatus)) {
                            throw new \DomainException("Invalid status transition from {$origStatus} to {$newStatus}.");
                        }
                    }
                }

                return parent::update($values);
            }

            public function delete()
            {
                $hasSealed = (clone $this)->where(function ($q) {
                    $q->whereNotNull('snapshot_hash')
                      ->orWhereNotNull('approved_at')
                      ->orWhereRaw("LOWER(status) IN ('approved', 'paid', 'void')");
                })->exists();

                if ($hasSealed) {
                    throw new \DomainException('Cannot bulk delete sealed, approved, paid, or void payroll records.');
                }

                return parent::delete();
            }
        };
    }

    /**
     * Defense-in-depth Eloquent ORM lifecycle guard:
     * Prevents direct model write bypasses (e.g. $payroll->status = 'paid'; $payroll->save();)
     * and blocks unauthorized financial or approval metadata mutation on sealed/approved payrolls.
     */
    protected static function booted(): void
    {
        static::updating(function (TeacherPayroll $payroll) {
            if ($payroll->isDirty('status')) {
                $originalStatus = (string) $payroll->getOriginal('status');
                $newStatus = (string) $payroll->status;

                $evalModel = clone $payroll;
                $evalModel->status = $originalStatus;

                if (!$evalModel->canTransitionTo($newStatus)) {
                    throw new \DomainException("Invalid status transition from {$originalStatus} to {$newStatus}.");
                }
            }

            $origHash = $payroll->getOriginal('snapshot_hash');
            $origStatus = strtolower((string) $payroll->getOriginal('status'));
            $origSealed = !empty($origHash)
                || $payroll->getOriginal('approved_at') !== null
                || in_array($origStatus, ['approved', 'paid', 'void'], true);

            // Protect immutable snapshot hash once generated
            if ($payroll->isDirty('snapshot_hash')) {
                if (!empty($origHash) && $payroll->snapshot_hash !== $origHash) {
                    throw new \DomainException('Cannot tamper with or modify an existing snapshot_hash.');
                }
            }

            // Protect approval metadata once set on a sealed/approved record
            if ($payroll->isDirty('approved_at') && $origSealed && $payroll->getOriginal('approved_at') !== null) {
                $origIso = \Carbon\Carbon::parse($payroll->getOriginal('approved_at'))->utc()->toIso8601String();
                $newIso = $payroll->approved_at !== null
                    ? \Carbon\Carbon::parse($payroll->approved_at)->utc()->toIso8601String()
                    : '';
                if ($origIso !== $newIso) {
                    throw new \DomainException("Cannot modify approval metadata 'approved_at' of an approved, paid, or void payroll.");
                }
            }

            if ($payroll->isDirty('approved_by') && $origSealed && $payroll->getOriginal('approved_by') !== null) {
                if ((string) $payroll->getOriginal('approved_by') !== (string) $payroll->approved_by) {
                    throw new \DomainException("Cannot modify approval metadata 'approved_by' of an approved, paid, or void payroll.");
                }
            }

            if ($payroll->isDirty('snapshot_hash_version') && !empty($origHash)) {
                if ((int) ($payroll->getOriginal('snapshot_hash_version') ?: 1) !== (int) ($payroll->snapshot_hash_version ?: 1)) {
                    throw new \DomainException("Cannot modify approval metadata 'snapshot_hash_version' of a sealed payroll.");
                }
            }

            // Protect all financial and accounting figures on sealed/approved/paid/void payrolls
            $financialFields = ['total_amount', 'total_hours', 'total_completed_lessons', 'teacher_id', 'month'];
            foreach ($financialFields as $field) {
                if ($payroll->isDirty($field) && $origSealed) {
                    $origVal = $payroll->getOriginal($field);
                    $newVal = $payroll->$field;
                    if (is_numeric($origVal) && is_numeric($newVal)) {
                        if (abs((float) $origVal - (float) $newVal) > 0.001) {
                            throw new \DomainException("Cannot modify financial figure '{$field}' of an approved, paid, or void payroll.");
                        }
                    } elseif ($origVal !== $newVal) {
                        throw new \DomainException("Cannot modify accounting attribute '{$field}' of an approved, paid, or void payroll.");
                    }
                }
            }
        });

        // Block direct deletion of finalized or approved payrolls at the ORM level
        static::deleting(function (TeacherPayroll $payroll) {
            if ($payroll->isSealedOrApproved()) {
                throw new \DomainException('Cannot delete an approved, paid, or void payroll record.');
            }
        });
    }

    /**
     * Determines whether the payroll is sealed, approved, or finalized.
     */
    public function isSealedOrApproved(): bool
    {
        $normalizedStatus = strtolower((string) $this->status);

        return !empty($this->snapshot_hash)
            || $this->approved_at !== null
            || in_array($normalizedStatus, ['approved', 'paid', 'void'], true);
    }

    /**
     * Determines whether the payroll possesses a valid, sealed snapshot hash.
     */
    public function hasValidSeal(string $timezone = 'Europe/Berlin'): bool
    {
        if (empty($this->snapshot_hash) || $this->approved_at === null) {
            return false;
        }

        $recalculatedHash = $this->calculateSnapshotHash($timezone);
        return hash_equals((string) $this->snapshot_hash, $recalculatedHash);
    }

    /**
     * Determines whether the payroll is in a terminal finalized state (paid or void).
     */
    public function isFinalized(): bool
    {
        $normalizedStatus = strtolower((string) $this->status);

        return in_array($normalizedStatus, ['paid', 'void'], true);
    }

    /**
     * Enforce strict financial accounting state transitions:
     * - draft -> processing, approved, void (CANNOT jump directly to paid)
     * - processing -> draft, approved, void (CANNOT jump directly to paid)
     * - approved -> paid, void (paid only if seal is valid; void only before payment)
     * - paid, void -> strictly terminal (cannot transition to ANY status, even to themselves)
     * - unknown/default -> false
     *
     * In addition, transitioning to 'paid' strictly requires that the record
     * has been approved and possesses a verified, untampered sealed snapshot hash.
     */
    public function canTransitionTo(string $targetStatus): bool
    {
        $current = strtolower((string) $this->status);
        $target = strtolower($targetStatus);

        // Terminal states cannot transition to ANY status, even to themselves
        if (in_array($current, ['paid', 'void'], true)) {
            return false;
        }

        if ($current === $target) {
            return true;
        }

        $allowed = match ($current) {
            'draft' => in_array($target, ['processing', 'void'], true),
            'processing' => in_array($target, ['draft', 'approved', 'void'], true),
            'approved' => in_array($target, ['paid', 'void'], true),
            default => false,
        };

        if (!$allowed) {
            return false;
        }

        // Strict Financial Integrity Gate:
        // Transitioning to 'approved' REQUIRES that the model has been sealed with a valid snapshot hash.
        if ($target === 'approved') {
            if (!$this->hasValidSeal()) {
                return false;
            }
        }

        // Transitioning to 'paid' REQUIRES a verified, untampered sealed snapshot hash.
        if ($target === 'paid') {
            if (!$this->hasValidSeal()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Computes the deterministic canonical Merkle-style SHA-256 snapshot hash
     * adhering to the system reference specification in PayrollCalculationService and PayrollReportDataFactory.
     */
    public function calculateSnapshotHash(string $timezone = 'Europe/Berlin'): string
    {
        $items = $this->exists ? $this->items()->get() : $this->items;
        $itemsSorted = $items->sortBy(fn($item) => (string) ($item->lesson_id ?? $item->id));

        $itemTokens = [];
        foreach ($itemsSorted as $item) {
            $hStr = number_format((float) $item->hours, 2, '.', '');
            $rStr = number_format((float) $item->hourly_rate, 2, '.', '');
            $aStr = number_format((float) $item->amount, 2, '.', '');
            $lId = (string) ($item->lesson_id ?? $item->id);

            $itemTokens[] = "{$lId}:{$hStr}:{$rStr}:{$aStr}";
        }

        $totHoursStr = number_format((float) $this->total_hours, 2, '.', '');
        $totAmountStr = number_format((float) $this->total_amount, 2, '.', '');
        $approvedAtIso = $this->approved_at
            ? \Carbon\Carbon::parse($this->approved_at)->utc()->toIso8601String()
            : '';
        $versionPrefix = 'v' . ((int) ($this->snapshot_hash_version ?: 1));

        $canonicalString = implode('|', [
            $versionPrefix,
            (string) $this->month,
            (string) $this->teacher_id,
            (string) $this->total_completed_lessons,
            $totHoursStr,
            $totAmountStr,
            $approvedAtIso,
            implode(',', $itemTokens),
        ]);

        return hash('sha256', $canonicalString);
    }
}
