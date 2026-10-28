<?php

namespace App\Modules\Nachhilfe\Domain\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PayrollGeneratorService
{
    /**
     * Generate a payroll for a teacher for a specific month.
     * @param Teacher $teacher
     * @param string $month Format: 'YYYY-MM'
     * @return TeacherPayroll|null Returns null if no lessons taught.
     */
    public function generateForTeacher(Teacher $teacher, string $month): ?TeacherPayroll
    {
        return DB::transaction(function () use ($teacher, $month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            // 1. Pessimistically lock the Teacher record first to serialize concurrent generation
            //    and ensure fresh hourly_rate is read under lock.
            /** @var Teacher $lockedTeacher */
            $lockedTeacher = Teacher::where('id', $teacher->id)->lockForUpdate()->firstOrFail();

            // 2. Find all completed lessons for the teacher in this month under the teacher lock
            $lessons = Lesson::with('subject')
                ->where('teacher_id', $lockedTeacher->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->get();

            if ($lessons->isEmpty()) {
                return null;
            }

            // 3. Check if any sealed or approved payroll already exists for this teacher and month (with pessimistic lock)
            $existingPayrolls = TeacherPayroll::where('teacher_id', $lockedTeacher->id)
                ->where('month', $month)
                ->lockForUpdate()
                ->get();

            $finalizedPayroll = $existingPayrolls->first(fn($p) => $p->isSealedOrApproved());
            if ($finalizedPayroll) {
                throw new \DomainException('A finalized payroll already exists for this month.');
            }

            // Strictly verify that ALL existing records are explicitly drafts or processing.
            // Any unknown, non-standard, or future status MUST NOT be silently deleted.
            foreach ($existingPayrolls as $existing) {
                $statusLower = strtolower((string) $existing->status);
                if (!in_array($statusLower, ['draft', 'processing'], true)) {
                    throw new \DomainException("Cannot regenerate payroll: existing record has non-draft status '{$existing->status}'.");
                }
            }

            // Only verified draft or processing records may be deleted and regenerated
            foreach ($existingPayrolls as $draftPayroll) {
                $draftPayroll->items()->delete();
                $draftPayroll->delete();
            }

            $totalMinutes = (int) $lessons->sum('duration_minutes');
            // Note: total_hours is derived from exact total minutes (round(totalMinutes / 60, 2)),
            // which may differ by ±0.01 from the sum of individually rounded item display hours.
            $totalHours = round($totalMinutes / 60, 2);
            $lessonCount = $lessons->count();
            $rate = (float) ($lockedTeacher->hourly_rate ?? 20.00);

            $payroll = TeacherPayroll::create([
                'teacher_id' => $lockedTeacher->id,
                'month' => $month,
                'total_completed_lessons' => $lessonCount,
                'total_hours' => $totalHours,
                'total_amount' => 0,
                'status' => 'Draft',
            ]);

            $totalAmount = 0.0;

            foreach ($lessons as $lesson) {
                $exactHours = ((int) $lesson->duration_minutes) / 60;
                $itemDisplayHours = round($exactHours, 2);
                // Earned pay is calculated from actual duration in minutes, not from rounded display hours
                $itemAmount = round($exactHours * $rate, 2);
                $totalAmount = round($totalAmount + $itemAmount, 2);

                $payroll->items()->create([
                    'lesson_id' => $lesson->id,
                    'amount' => $itemAmount,
                    'hours' => $itemDisplayHours,
                    'hourly_rate' => $rate,
                ]);
            }

            $payroll->update([
                'total_completed_lessons' => $lessonCount,
                'total_hours' => $totalHours,
                'total_amount' => $totalAmount,
            ]);

            return $payroll;
        });
    }
}
