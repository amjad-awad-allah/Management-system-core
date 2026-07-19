<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\SubscriptionUsage;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionUsageService
{
    /**
     * Deduct hours for a completed lesson for all attending students.
     * Must be idempotent.
     */
    public function deductForLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            $students = $lesson->students;

            foreach ($students as $student) {
                // Find the specific pivot record for this lesson
                $lessonStudent = $lesson->lessonStudents()->where('student_id', $student->id)->first();
                if (!$lessonStudent) {
                    continue; // Student was removed or doesn't exist in pivot
                }

                // IDEMPOTENCY CHECK: Did we already consume hours for this student on this lesson?
                if ($lessonStudent->hours_consumed > 0) {
                    continue; // Already processed
                }

                $subscriptionId = $lessonStudent->package_id; // References student_packages.id
                
                if (!$subscriptionId) {
                    // Find the best available package to deduct hours from (Active first, then Pending Approval; ordered by Expiration date soonest first)
                    $activePackage = StudentPackage::where('student_id', $student->id)
                        ->whereIn('status', ['active', 'Active', 'pending_approval'])
                        ->where('remaining_hours', '>=', ($lesson->duration_minutes / 60))
                        ->orderByRaw("CASE WHEN status IN ('active', 'Active') THEN 0 ELSE 1 END ASC")
                        ->orderByRaw("expires_at IS NULL, expires_at ASC")
                        ->first();
                    
                    if (!$activePackage) {
                        throw new Exception("No active subscription found with sufficient hours for student: {$student->name}");
                    }
                    $subscriptionId = $activePackage->id;
                    
                    // Update pivot
                    $lessonStudent->package_id = $subscriptionId;
                    $lessonStudent->save();
                }

                $hoursToDeduct = round($lesson->duration_minutes / 60, 2);

                // Lock row
                $subscription = StudentPackage::where('id', $subscriptionId)->lockForUpdate()->firstOrFail();

                if ($subscription->remaining_hours < $hoursToDeduct) {
                    throw new Exception("Insufficient hours in subscription {$subscriptionId} for student {$student->name}.");
                }

                // Create the Usage Record
                SubscriptionUsage::create([
                    'subscription_id' => $subscription->id,
                    'lesson_id' => $lesson->id,
                    'type' => 'Deduction',
                    'hours' => $hoursToDeduct,
                    'created_by' => null, // System event
                ]);

                // Update Subscription
                $subscription->remaining_hours -= $hoursToDeduct;
                if ($subscription->remaining_hours <= 0) {
                    $subscription->status = 'exhausted';
                }
                $subscription->save();

                // Mark pivot as consumed
                $lessonStudent->hours_consumed = $hoursToDeduct;
                $lessonStudent->save();
            }
        });
    }
}
