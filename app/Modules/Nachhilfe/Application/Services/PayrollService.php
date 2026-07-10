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

            $teacher = $lesson->teacher;
            if (!$teacher) {
                return; // Lesson has no teacher? Edge case.
            }

            $lessonMonth = Carbon::parse($lesson->date)->format('Y-m');
            $hours = round($lesson->duration_minutes / 60, 2);
            $hourlyRate = $teacher->hourly_rate ?? 0;
            $amount = $hours * $hourlyRate;

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

            // Update payroll totals
            $payroll->total_completed_lessons += 1;
            $payroll->total_hours += $hours;
            $payroll->total_amount += $amount;
            $payroll->save();
        });
    }
}
