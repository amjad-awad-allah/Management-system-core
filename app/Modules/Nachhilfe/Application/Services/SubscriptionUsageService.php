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

                // Check attendance: do not deduct if the student has marked excused absence
                $attendance = $lessonStudent->attendance;
                if ($attendance && $attendance->status === 'absent_excused') {
                    continue; 
                }

                $subscriptionId = $lessonStudent->package_id; // References student_packages.id
                
                if (!$subscriptionId) {
                    // Find the best available package to deduct hours from (Active first, then Pending Approval; ordered by Expiration date soonest first)
                    $activePackage = StudentPackage::where('student_id', $student->id)
                        ->whereIn('status', ['active', 'pending_approval'])
                        ->where('remaining_hours', '>=', ($lesson->duration_minutes / 60))
                        ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END ASC")
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
                    'notes' => 'Lesson attended',
                    'created_by' => null, // System event
                ]);

                // Update Subscription
                $subscription->remaining_hours -= $hoursToDeduct;
                if ($subscription->remaining_hours <= 0) {
                    $subscription->status = 'exhausted';
                }
                $subscription->save();

                // Trigger real-time low hours alert check
                try {
                    app(PackageAlertService::class)->checkAndTriggerLowHours($subscription->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to check low hours alerts: " . $e->getMessage());
                }

                // Mark pivot as consumed
                $lessonStudent->hours_consumed = $hoursToDeduct;
                $lessonStudent->save();
            }
        });
    }

    /**
     * Deduct hours for a cancelled lesson (late cancellation) for all attending students.
     * Must be idempotent.
     */
    public function deductForCancellation(\App\Modules\Nachhilfe\Infrastructure\Models\Lesson $lesson, float $deductPercentage, string $reason): void
    {
        DB::transaction(function () use ($lesson, $deductPercentage, $reason) {
            $students = $lesson->students;

            foreach ($students as $student) {
                $lessonStudent = $lesson->lessonStudents()->where('student_id', $student->id)->first();
                if (!$lessonStudent) {
                    continue; 
                }

                if ($lessonStudent->hours_consumed > 0) {
                    continue; 
                }

                $subscriptionId = $lessonStudent->package_id;
                
                if (!$subscriptionId) {
                    $activePackage = StudentPackage::where('student_id', $student->id)
                        ->whereIn('status', ['active', 'pending_approval'])
                        ->where('remaining_hours', '>=', ($lesson->duration_minutes / 60))
                        ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END ASC")
                        ->orderByRaw("expires_at IS NULL, expires_at ASC")
                        ->first();
                    
                    if (!$activePackage) {
                        \Illuminate\Support\Facades\Log::warning("No active package for cancellation deduction for student: {$student->id}");
                        continue;
                    }
                    $subscriptionId = $activePackage->id;
                    $lessonStudent->package_id = $subscriptionId;
                    $lessonStudent->save();
                }

                $hoursToDeduct = round(($lesson->duration_minutes / 60) * ($deductPercentage / 100), 2);
                if ($hoursToDeduct <= 0) {
                    continue;
                }

                $subscription = StudentPackage::where('id', $subscriptionId)->lockForUpdate()->firstOrFail();

                if ($subscription->remaining_hours < $hoursToDeduct) {
                    $hoursToDeduct = (float) $subscription->remaining_hours;
                }

                SubscriptionUsage::create([
                    'subscription_id' => $subscription->id,
                    'lesson_id' => $lesson->id,
                    'type' => 'Deduction',
                    'hours' => $hoursToDeduct,
                    'notes' => $reason,
                    'created_by' => null,
                ]);

                $subscription->remaining_hours -= $hoursToDeduct;
                if ($subscription->remaining_hours <= 0) {
                    $subscription->status = 'exhausted';
                }
                $subscription->save();

                try {
                    app(PackageAlertService::class)->checkAndTriggerLowHours($subscription->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to check low hours: " . $e->getMessage());
                }

                $lessonStudent->hours_consumed = $hoursToDeduct;
                $lessonStudent->save();
            }
        });
    }
}
