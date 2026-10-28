<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    private string $timezone = 'Europe/Berlin';

    /**
     * Compute real-time draft summary or fetch approved payroll snapshot.
     */
    public function getPayrollSummary(string $yearMonth): array
    {
        $approvedPayrolls = TeacherPayroll::with(['teacher', 'items.lesson'])
            ->where('month', $yearMonth)
            ->whereIn('status', ['approved', 'Approved', 'paid', 'Paid'])
            ->get();

        if ($approvedPayrolls->isNotEmpty()) {
            $teacherSummaries = [];
            $totalCompletedLessons = 0;
            $totalCenterHours = 0.0;
            $totalCenterPayout = 0.0;

            foreach ($approvedPayrolls as $payroll) {
                $totalCompletedLessons += $payroll->total_completed_lessons;
                $totalCenterHours += (float) $payroll->total_hours;
                $totalCenterPayout += (float) $payroll->total_amount;

                $teacherSummaries[] = [
                    'payroll_id' => $payroll->id,
                    'teacher_id' => $payroll->teacher_id,
                    'teacher_name' => $payroll->teacher?->name ?? 'Unknown Teacher',
                    'hourly_rate' => (float) ($payroll->items->first()?->hourly_rate ?? $payroll->teacher?->hourly_rate ?? 0),
                    'lesson_count' => $payroll->total_completed_lessons,
                    'total_minutes' => (int) round(((float) $payroll->total_hours) * 60),
                    'total_hours' => (float) $payroll->total_hours,
                    'total_payout' => (float) $payroll->total_amount,
                    'snapshot_hash' => $payroll->snapshot_hash,
                    'approved_at' => $payroll->approved_at?->toIso8601String(),
                ];
            }

            return [
                'status' => 'approved',
                'year_month' => $yearMonth,
                'unverified_lessons_count' => 0,
                'can_approve' => false,
                'total_completed_lessons' => $totalCompletedLessons,
                'total_center_hours' => round($totalCenterHours, 2),
                'total_center_payout' => round($totalCenterPayout, 2),
                'teachers' => $teacherSummaries,
            ];
        }

        // Compute Draft Payroll
        $startOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->endOfMonth()->toDateString();
        $now = Carbon::now($this->timezone);

        $lessons = Lesson::with(['teacher'])
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // 1. Detect unverified past lessons (scheduled status but end_time < now)
        $unverifiedCount = 0;
        foreach ($lessons as $lesson) {
            $normalizedStatus = strtolower($lesson->status);
            if ($normalizedStatus === 'scheduled') {
                $lessonEnd = Carbon::parse("{$lesson->date} {$lesson->end_time}", $this->timezone);
                if ($lessonEnd->lte($now)) {
                    $unverifiedCount++;
                }
            }
        }

        // 2. Filter completed lessons for payout calculation
        $completedLessons = $lessons->filter(function ($l) {
            return strtolower($l->status) === 'completed';
        });

        $groupedByTeacher = $completedLessons->groupBy('teacher_id');
        $teacherSummaries = [];
        $totalCompletedLessons = 0;
        $totalCenterHours = 0.0;
        $totalCenterPayout = 0.0;

        foreach ($groupedByTeacher as $teacherId => $tLessons) {
            /** @var Teacher|null $teacher */
            $teacher = Teacher::find($teacherId);
            if (!$teacher) continue;

            $totalMinutes = (int) $tLessons->sum('duration_minutes');
            $totalHours = round($totalMinutes / 60, 2);
            $hourlyRate = (float) $teacher->hourly_rate;
            $totalPayout = 0.0;
            foreach ($tLessons as $lesson) {
                $exactHours = ((int) $lesson->duration_minutes) / 60;
                $totalPayout = round($totalPayout + round($exactHours * $hourlyRate, 2), 2);
            }

            $totalCompletedLessons += count($tLessons);
            $totalCenterHours += $totalHours;
            $totalCenterPayout = round($totalCenterPayout + $totalPayout, 2);

            $teacherSummaries[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'hourly_rate' => $hourlyRate,
                'lesson_count' => count($tLessons),
                'total_minutes' => $totalMinutes,
                'total_hours' => $totalHours,
                'total_payout' => $totalPayout,
            ];
        }

        return [
            'status' => 'draft',
            'year_month' => $yearMonth,
            'unverified_lessons_count' => $unverifiedCount,
            'can_approve' => $unverifiedCount === 0 && count($completedLessons) > 0,
            'total_completed_lessons' => $totalCompletedLessons,
            'total_center_hours' => round($totalCenterHours, 2),
            'total_center_payout' => round($totalCenterPayout, 2),
            'teachers' => $teacherSummaries,
        ];
    }

    /**
     * Atomically approve month payroll and create frozen historical snapshot.
     */
    public function approvePayroll(string $yearMonth, string $approvedByUserId): array
    {
        return DB::transaction(function () use ($yearMonth, $approvedByUserId) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->endOfMonth()->toDateString();

            // 1. Acquire deterministic pessimistic locks on involved Teacher rows FIRST
            //    so concurrent approval calls for an empty month serialize on the Teacher rows.
            $monthTeacherIds = Lesson::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->pluck('teacher_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $lockedTeachers = collect();
            if (!empty($monthTeacherIds)) {
                $lockedTeachers = Teacher::whereIn('id', $monthTeacherIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
            }

            // 2. Re-check existing payrolls for this month under lock AFTER acquiring Teacher locks
            $existingPayrolls = TeacherPayroll::where('month', $yearMonth)
                ->lockForUpdate()
                ->get();

            $existingApproved = $existingPayrolls->first(fn($p) => $p->isSealedOrApproved());
            if ($existingApproved) {
                return $this->getPayrollSummary($yearMonth);
            }

            // Strictly verify that ALL existing records are explicitly lesson-generated drafts or processing.
            // Any unknown status or manual payroll draft must NOT be bypassed or silently overwritten.
            foreach ($existingPayrolls as $existing) {
                $statusLower = strtolower((string) $existing->status);
                if (!in_array($statusLower, ['draft', 'processing'], true)) {
                    throw new \DomainException("Cannot approve payroll: existing payroll has non-draft status '{$existing->status}'.");
                }

                $itemsCount = $existing->items()->count();
                $itemsSum = round((float) $existing->items()->sum('amount'), 2);
                if ($itemsCount === 0 || abs((float) $existing->total_amount - $itemsSum) > 0.001) {
                    throw ValidationException::withMessages([
                        'payroll' => [
                            "Cannot approve payroll for {$yearMonth}. A manual payroll draft exists for teacher {$existing->teacher_id} and cannot be automatically overwritten. Please review or remove the manual draft first."
                        ],
                    ]);
                }
            }

            // 3. Validate approval preconditions BEFORE deleting any existing draft records
            $draft = $this->getPayrollSummary($yearMonth);

            if ($draft['unverified_lessons_count'] > 0) {
                throw ValidationException::withMessages([
                    'payroll' => ["Cannot approve payroll for {$yearMonth}. There are {$draft['unverified_lessons_count']} past scheduled lessons requiring verification."],
                ]);
            }

            if ($draft['total_completed_lessons'] === 0) {
                throw ValidationException::withMessages([
                    'payroll' => ["Cannot approve payroll for {$yearMonth}. No completed lessons found for this month."],
                ]);
            }

            // 4. Remove existing drafts only after preconditions are verified
            foreach ($existingPayrolls as $draftPayroll) {
                $draftPayroll->items()->delete();
                $draftPayroll->delete();
            }

            $completedLessons = Lesson::with(['teacher'])
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get()
                ->filter(fn($l) => strtolower($l->status) === 'completed')
                ->groupBy('teacher_id');

            $approvedPayrolls = [];
            $approvedAt = Carbon::now($this->timezone);

            foreach ($completedLessons->sortKeys() as $teacherId => $lessons) {
                /** @var Teacher $teacher */
                $teacher = $lockedTeachers->get($teacherId)
                    ?? Teacher::where('id', $teacherId)->lockForUpdate()->firstOrFail();

                $totalMinutes = (int) $lessons->sum('duration_minutes');
                // Note: total_hours is derived from exact total minutes (round(totalMinutes / 60, 2)),
                // which may differ by ±0.01 from the sum of individually rounded item display hours.
                $totalHours = round($totalMinutes / 60, 2);
                $hourlyRate = (float) $teacher->hourly_rate;
                $lessonCount = count($lessons);

                $totalAmount = 0.0;
                $preparedItems = [];
                foreach ($lessons as $lesson) {
                    $lMinutes = (int) $lesson->duration_minutes;
                    $exactHours = $lMinutes / 60;
                    $lHours = round($exactHours, 2);
                    // Earned pay is calculated from actual duration in minutes, not from rounded display hours
                    $lAmount = round($exactHours * $hourlyRate, 2);
                    $totalAmount = round($totalAmount + $lAmount, 2);

                    $preparedItems[] = [
                        'id' => (string) Str::ulid(),
                        'lesson_id' => $lesson->id,
                        'hours' => $lHours,
                        'hourly_rate' => $hourlyRate,
                        'amount' => $lAmount,
                    ];
                }

                $payroll = TeacherPayroll::create([
                    'id' => (string) Str::ulid(),
                    'teacher_id' => $teacher->id,
                    'month' => $yearMonth,
                    'total_completed_lessons' => $lessonCount,
                    'total_hours' => $totalHours,
                    'total_amount' => $totalAmount,
                    'status' => 'processing',
                    'approved_at' => null,
                    'approved_by' => null,
                    'snapshot_hash' => null,
                    'snapshot_hash_version' => 1,
                ]);

                foreach ($preparedItems as $itemData) {
                    TeacherPayrollItem::create(array_merge($itemData, [
                        'payroll_id' => $payroll->id,
                    ]));
                }

                // Centralized snapshot hash calculation after all items exist in DB
                $payroll->load('items');
                $payroll->approved_at = $approvedAt;
                $payroll->approved_by = $approvedByUserId;
                $snapshotHash = $payroll->calculateSnapshotHash($this->timezone);
                $payroll->snapshot_hash = $snapshotHash;
                $payroll->status = 'approved';
                $payroll->save();

                $approvedPayrolls[] = $payroll;
            }

            return $this->getPayrollSummary($yearMonth);
        });
    }
}
