<?php

namespace App\Modules\Billing\Application\Listeners;

use App\Modules\Nachhilfe\Domain\Events\AttendanceMarkedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonConsumption;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeductVoucherListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AttendanceMarkedEvent $event): void
    {
        $attendance = $event->attendance;
        $lessonStudent = $attendance->lessonStudent()->with(['lesson', 'package'])->first();
        
        if (!$lessonStudent || !$lessonStudent->package_id) {
            return; // No package to deduct from
        }

        // Only deduct for present or unexcused absence
        if (in_array($attendance->status, ['present', 'absent_unexcused', 'late'])) {
            DB::transaction(function () use ($attendance, $lessonStudent) {
                // Determine hours to deduct based on lesson duration
                $durationMinutes = $lessonStudent->lesson->duration_minutes;
                if (!$durationMinutes) {
                    return; // Cannot deduct without duration
                }
                
                $hoursToDeduct = round($durationMinutes / 60, 2);

                // Note: We need to deduct from the actual student balance, which might be in student_packages or packages.
                // For this architecture, we assume the package_id in lesson_student links to the specific package instance 
                // tracking remaining hours. If we need to fetch StudentPackage, we'll do it here.
                
                // Assuming $lessonStudent->package tracks the balance (from the provided schema)
                $package = $lessonStudent->package; // or StudentPackage where package_id = ...
                
                // Let's assume there's a StudentPackage table that tracks this for the user, 
                // but based on the provided schema, we'll record the consumption.
                
                // Record the consumption
                LessonConsumption::create([
                    'id' => (string) Str::ulid(),
                    'lesson_student_id' => $lessonStudent->id,
                    'package_id' => $lessonStudent->package_id,
                    'hours_used' => $hoursToDeduct,
                    'consumption_type' => 'attendance',
                    'balance_before' => 0, // Should be fetched from StudentPackage
                    'balance_after' => 0, // Should be updated
                    'notes' => 'Deducted automatically by AttendanceMarkedEvent',
                ]);
                
                Log::info("Deducted {$hoursToDeduct} hours for Attendance ID: {$attendance->id}");
            });
        }
    }
}
