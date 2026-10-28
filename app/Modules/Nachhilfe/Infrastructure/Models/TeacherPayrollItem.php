<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherPayrollItem extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function payroll()
    {
        return $this->belongsTo(TeacherPayroll::class, 'payroll_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Create a new Eloquent query builder that intercepts Eloquent mass inserts, upserts, updates, and deletions on items of sealed payrolls.
     */
    public function newEloquentBuilder($query)
    {
        return new class($query) extends \Illuminate\Database\Eloquent\Builder {
            private function assertTargetPayrollsUnsealed(array $rows, string $message): void
            {
                $payrollIds = [];
                $itemIds = [];
                $lessonIds = [];

                foreach ($rows as $row) {
                    if (is_array($row)) {
                        if (!empty($row['payroll_id'])) {
                            $payrollIds[] = $row['payroll_id'];
                        }
                        if (!empty($row['id'])) {
                            $itemIds[] = $row['id'];
                        }
                        if (!empty($row['lesson_id'])) {
                            $lessonIds[] = $row['lesson_id'];
                        }
                    }
                }

                $payrollIds = array_unique($payrollIds);
                if (!empty($payrollIds)) {
                    $parents = TeacherPayroll::whereIn('id', $payrollIds)->get();
                    if ($parents->contains(fn(TeacherPayroll $p) => $p->isSealedOrApproved())) {
                        throw new \DomainException($message);
                    }
                }

                if (!empty($itemIds) || !empty($lessonIds)) {
                    $existingSealed = TeacherPayrollItem::query()
                        ->where(function ($q) use ($itemIds, $lessonIds) {
                            if (!empty($itemIds)) {
                                $q->orWhereIn('id', array_unique($itemIds));
                            }
                            if (!empty($lessonIds)) {
                                $q->orWhereIn('lesson_id', array_unique($lessonIds));
                            }
                        })
                        ->whereHas('payroll', function ($q) {
                            $q->whereNotNull('snapshot_hash')
                              ->orWhereNotNull('approved_at')
                              ->orWhereRaw("LOWER(status) IN ('approved', 'paid', 'void')");
                        })
                        ->exists();

                    if ($existingSealed) {
                        throw new \DomainException($message);
                    }
                }
            }

            public function insert(array $values)
            {
                if (empty($values)) {
                    return true;
                }

                $rows = is_array(reset($values)) ? $values : [$values];
                $this->assertTargetPayrollsUnsealed(
                    $rows,
                    'Cannot add items to a sealed payroll record.'
                );

                return $this->toBase()->insert($values);
            }

            public function insertOrIgnore(array $values)
            {
                if (empty($values)) {
                    return 0;
                }

                $rows = is_array(reset($values)) ? $values : [$values];
                $this->assertTargetPayrollsUnsealed(
                    $rows,
                    'Cannot add items to a sealed payroll record.'
                );

                return $this->toBase()->insertOrIgnore($values);
            }

            public function upsert(array $values, $uniqueBy, $update = null)
            {
                if (empty($values)) {
                    return 0;
                }

                $rows = is_array(reset($values)) ? $values : [$values];
                $this->assertTargetPayrollsUnsealed(
                    $rows,
                    'Cannot modify payroll items belonging to an approved, paid, or void payroll.'
                );

                return parent::upsert($values, $uniqueBy, $update);
            }

            public function update(array $values)
            {
                $hasSealedParent = (clone $this)->whereHas('payroll', function ($q) {
                    $q->whereNotNull('snapshot_hash')
                      ->orWhereNotNull('approved_at')
                      ->orWhereRaw("LOWER(status) IN ('approved', 'paid', 'void')");
                })->exists();

                if ($hasSealedParent) {
                    throw new \DomainException('Cannot modify payroll items belonging to an approved, paid, or void payroll.');
                }

                if (array_key_exists('payroll_id', $values) && !empty($values['payroll_id'])) {
                    $targetPayroll = TeacherPayroll::find($values['payroll_id']);
                    if ($targetPayroll && $targetPayroll->isSealedOrApproved()) {
                        throw new \DomainException('Cannot reassign payroll items to an approved, paid, or void payroll.');
                    }
                }

                return parent::update($values);
            }

            public function delete()
            {
                $hasSealedParent = (clone $this)->whereHas('payroll', function ($q) {
                    $q->whereNotNull('snapshot_hash')
                      ->orWhereNotNull('approved_at')
                      ->orWhereRaw("LOWER(status) IN ('approved', 'paid', 'void')");
                })->exists();

                if ($hasSealedParent) {
                    throw new \DomainException('Cannot delete payroll items belonging to an approved, paid, or void payroll.');
                }

                return parent::delete();
            }
        };
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherPayrollItem $item) {
            if (!empty($item->payroll_id)) {
                $parent = TeacherPayroll::find($item->payroll_id);
                if ($parent && $parent->isSealedOrApproved()) {
                    throw new \DomainException('Cannot add items to a sealed payroll record.');
                }
            }
        });

        static::updating(function (TeacherPayrollItem $item) {
            $payrollIds = array_unique(array_filter([
                $item->getOriginal('payroll_id'),
                $item->payroll_id,
            ]));

            if (!empty($payrollIds)) {
                $parents = TeacherPayroll::whereIn('id', $payrollIds)->get();
                if ($parents->contains(fn(TeacherPayroll $p) => $p->isSealedOrApproved())) {
                    throw new \DomainException('Cannot modify payroll items belonging to an approved, paid, or void payroll.');
                }
            }
        });

        static::deleting(function (TeacherPayrollItem $item) {
            $payrollId = $item->getOriginal('payroll_id') ?: $item->payroll_id;
            if (!empty($payrollId)) {
                $parent = TeacherPayroll::find($payrollId);
                if ($parent && $parent->isSealedOrApproved()) {
                    throw new \DomainException('Cannot delete payroll items belonging to an approved, paid, or void payroll.');
                }
            }
        });
    }
}
