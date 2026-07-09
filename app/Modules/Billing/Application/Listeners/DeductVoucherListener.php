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
        $lessonStudent = $attendance->lessonStudent()->with('lesson')->first();
        
        if (!$lessonStudent) {
            return;
        }

        // Only deduct for present or unexcused absence or late
        if (in_array($attendance->status, ['present', 'absent_unexcused', 'late'])) {
            DB::transaction(function () use ($attendance, $lessonStudent) {
                $durationMinutes = $lessonStudent->lesson->duration_minutes;
                if (!$durationMinutes) {
                    return;
                }
                
                $hoursToDeduct = round($durationMinutes / 60, 2);

                // Find active package for student
                $studentPackage = StudentPackage::where('student_id', $lessonStudent->student_id)
                    ->where('status', 'active')
                    ->where('remaining_hours', '>=', $hoursToDeduct)
                    ->first();

                if (!$studentPackage) {
                    Log::warning("No active package with enough hours for student {$lessonStudent->student_id}");
                    return;
                }

                $balanceBefore = $studentPackage->remaining_hours;
                $studentPackage->remaining_hours -= $hoursToDeduct;

                if ($studentPackage->remaining_hours <= 0) {
                    $studentPackage->status = 'exhausted';
                }
                $studentPackage->save();

                // Record the consumption
                LessonConsumption::create([
                    'id' => (string) Str::ulid(),
                    'lesson_student_id' => $lessonStudent->id,
                    'package_id' => $studentPackage->package_id,
                    'hours_used' => $hoursToDeduct,
                    'consumption_type' => 'attendance',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $studentPackage->remaining_hours,
                    'notes' => 'Deducted automatically by AttendanceMarkedEvent',
                ]);
                
                Log::info("Deducted {$hoursToDeduct} hours for Attendance ID: {$attendance->id} from StudentPackage ID: {$studentPackage->id}");
            });
        }
    }
}
