<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Add a completed lesson to the teacher's payroll for the corresponding month.
     * Must be idempotent.
     */
    public function generateItemForLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            // IDEMPOTENCY CHECK
            $existingItem = TeacherPayrollItem::where('lesson_id', $lesson->id)->first();
            if ($existingItem) {
                return; // Already processed
            }

            $teacher = !empty($lesson->teacher_id)
                ? \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('id', $lesson->teacher_id)->lockForUpdate()->first()
                : $lesson->teacher;
            if (!$teacher) {
                return; // Lesson has no teacher? Edge case.
            }

            $lessonMonth = Carbon::parse($lesson->date)->format('Y-m');
            $exactHours = ((int) $lesson->duration_minutes) / 60;
            $hours = round($exactHours, 2);
            $hourlyRate = (float) ($teacher->hourly_rate ?? 0);
            // Earned pay is calculated from actual duration in minutes, not from rounded display hours
            $amount = round($exactHours * $hourlyRate, 2);

            // Find or Create Payroll for that month
            $payroll = TeacherPayroll::firstOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'month' => $lessonMonth,
                ],
                [
                    'total_completed_lessons' => 0,
                    'total_hours' => 0,
                    'total_amount' => 0,
                    'status' => 'Draft'
                ]
            );

            // Lock payroll for update to update totals
            $payroll = TeacherPayroll::where('id', $payroll->id)->lockForUpdate()->first();

            // Create item (snapshotting the hourly_rate)
            TeacherPayrollItem::create([
                'payroll_id' => $payroll->id,
                'lesson_id' => $lesson->id,
                'hours' => $hours,
                'hourly_rate' => $hourlyRate,
                'amount' => $amount,
            ]);

            // Update payroll totals from actual lesson durations and rounded item amounts
            $allItems = $payroll->items()->with('lesson')->get();
            $totalMinutes = $allItems->sum(fn($item) => (int) ($item->lesson?->duration_minutes ?? round(((float) $item->hours) * 60)));
            $payroll->total_completed_lessons = $allItems->count();
            $payroll->total_hours = round($totalMinutes / 60, 2);
            $payroll->total_amount = round((float) $allItems->sum('amount'), 2);
            $payroll->save();
        });
    }
}
