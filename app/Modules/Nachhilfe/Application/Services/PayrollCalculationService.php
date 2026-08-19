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
            ->where('status', 'approved')
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

            $totalMinutes = $tLessons->sum('duration_minutes');
            $totalHours = round($totalMinutes / 60, 2);
            $hourlyRate = (float) $teacher->hourly_rate;
            $totalPayout = round($totalHours * $hourlyRate, 2);

            $totalCompletedLessons += count($tLessons);
            $totalCenterHours += $totalHours;
            $totalCenterPayout += $totalPayout;

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
            // Pessimistic locking check for idempotency
            $existingApproved = TeacherPayroll::where('month', $yearMonth)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->get();

            if ($existingApproved->isNotEmpty()) {
                return $this->getPayrollSummary($yearMonth);
            }

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

            $startOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromFormat('Y-m', $yearMonth, $this->timezone)->endOfMonth()->toDateString();

            $completedLessons = Lesson::with(['teacher'])
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get()
                ->filter(fn($l) => strtolower($l->status) === 'completed')
                ->groupBy('teacher_id');

            $approvedPayrolls = [];
            $approvedAt = Carbon::now($this->timezone);

            foreach ($completedLessons as $teacherId => $lessons) {
                /** @var Teacher $teacher */
                $teacher = Teacher::findOrFail($teacherId);
                $totalMinutes = $lessons->sum('duration_minutes');
                $totalHours = round($totalMinutes / 60, 2);
                $hourlyRate = (float) $teacher->hourly_rate;
                $totalAmount = round($totalHours * $hourlyRate, 2);
                $lessonCount = count($lessons);

                // Compute sorted item details for Merkle-style canonical integrity
                $itemTokens = [];
                foreach ($lessons->sortBy('id') as $lesson) {
                    $lMinutes = $lesson->duration_minutes;
                    $lHours = round($lMinutes / 60, 2);
                    $lAmount = round($lHours * $hourlyRate, 2);
                    $itemTokens[] = implode(':', [
                        $lesson->id,
                        number_format($lHours, 2, '.', ''),
                        number_format($hourlyRate, 2, '.', ''),
                        number_format($lAmount, 2, '.', ''),
                    ]);
                }

                $approvedAt = Carbon::now($this->timezone);
                $approvedAtIso = Carbon::parse($approvedAt->toDateTimeString(), $this->timezone)->toIso8601String();

                // Compute deterministic canonical SHA-256 snapshot hash
                $canonicalString = implode('|', [
                    'v1',
                    $yearMonth,
                    $teacher->id,
                    $lessonCount,
                    number_format($totalHours, 2, '.', ''),
                    number_format($totalAmount, 2, '.', ''),
                    $approvedAtIso,
                    implode(',', $itemTokens),
                ]);
                $snapshotHash = hash('sha256', $canonicalString);

                $payroll = TeacherPayroll::create([
                    'id' => (string) Str::ulid(),
                    'teacher_id' => $teacher->id,
                    'month' => $yearMonth,
                    'total_completed_lessons' => $lessonCount,
                    'total_hours' => $totalHours,
                    'total_amount' => $totalAmount,
                    'status' => 'approved',
                    'approved_at' => $approvedAt,
                    'approved_by' => $approvedByUserId,
                    'snapshot_hash' => $snapshotHash,
                    'snapshot_hash_version' => 1,
                ]);

                foreach ($lessons as $lesson) {
                    $lMinutes = $lesson->duration_minutes;
                    $lHours = round($lMinutes / 60, 2);
                    $lAmount = round($lHours * $hourlyRate, 2);

                    TeacherPayrollItem::create([
                        'id' => (string) Str::ulid(),
                        'payroll_id' => $payroll->id,
                        'lesson_id' => $lesson->id,
                        'hours' => $lHours,
                        'hourly_rate' => $hourlyRate,
                        'amount' => $lAmount,
                    ]);
                }

                $approvedPayrolls[] = $payroll;
            }

            return $this->getPayrollSummary($yearMonth);
        });
    }
}
