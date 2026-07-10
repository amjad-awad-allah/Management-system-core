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

            // Find all completed lessons for the teacher in this month
            $lessons = Lesson::with('subject')
                ->where('teacher_id', $teacher->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->get();

            if ($lessons->isEmpty()) {
                return null;
            }

            // Throw if a finalized payroll already exists
            $finalizedPayroll = TeacherPayroll::where('teacher_id', $teacher->id)
                ->where('month', $month)
                ->whereIn('status', ['Paid', 'Void'])
                ->first();
            
            if ($finalizedPayroll) {
                throw new \DomainException('A finalized payroll already exists for this month.');
            }

            $existingPayroll = TeacherPayroll::where('teacher_id', $teacher->id)
                ->where('month', $month)
                ->whereIn('status', ['Draft', 'Processing'])
                ->first();

            // If it's already Paid, we probably shouldn't regenerate it.
            // For now, let's assume we can regenerate Drafts.
            if ($existingPayroll) {
                $existingPayroll->items()->delete();
                $existingPayroll->forceDelete();
            }

            $payroll = TeacherPayroll::create([
                'teacher_id' => $teacher->id,
                'month' => $month,
                'total_amount' => 0,
                'status' => 'Draft',
            ]);

            $totalAmount = 0;
            $rate = $teacher->hourly_rate ?? 20.00;

            foreach ($lessons as $lesson) {
                $hours = $lesson->duration_minutes / 60;
                $amount = $hours * $rate;
                $totalAmount += $amount;

                $payroll->items()->create([
                    'lesson_id' => $lesson->id,
                    'amount' => $amount,
                    'hours' => $hours,
                    'hourly_rate' => $rate,
                ]);
            }

            $payroll->update(['total_amount' => $totalAmount]);

            return $payroll;
        });
    }
}
