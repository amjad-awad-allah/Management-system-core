<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentContract;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentDocument;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $studentId = $student->id;

        // Fetch related IDs
        $contractIds = StudentContract::where('student_id', $studentId)->pluck('id')->toArray();
        $packageIds = StudentPackage::where('student_id', $studentId)->pluck('id')->toArray();
        $lessonStudentIds = LessonStudent::where('student_id', $studentId)->pluck('id')->toArray();
        $attendanceIds = Attendance::whereIn('lesson_student_id', $lessonStudentIds)->pluck('id')->toArray();
        $activeDocIds = StudentDocument::where('student_id', $studentId)->pluck('id')->toArray();
        $deletedDocIds = DB::table('audit_logs')
            ->where('auditable_type', StudentDocument::class)
            ->where(function($q) use ($studentId) {
                $q->where('new_values', 'like', "%{$studentId}%")
                  ->orWhere('old_values', 'like', "%{$studentId}%");
            })
            ->pluck('auditable_id')
            ->toArray();
        $documentIds = array_unique(array_merge($activeDocIds, $deletedDocIds));

        // Query audit logs
        $logs = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->where(function ($q) use ($studentId, $contractIds, $packageIds, $lessonStudentIds, $attendanceIds, $documentIds) {
                // Student logs
                $q->where(function ($sq) use ($studentId) {
                    $sq->where('auditable_type', Student::class)
                       ->where('auditable_id', $studentId);
                });
                
                // Contract logs
                if (!empty($contractIds)) {
                    $q->orWhere(function ($cq) use ($contractIds) {
                        $cq->where('auditable_type', StudentContract::class)
                           ->whereIn('auditable_id', $contractIds);
                    });
                }
                
                // Package logs
                if (!empty($packageIds)) {
                    $q->orWhere(function ($pq) use ($packageIds) {
                        $pq->where('auditable_type', StudentPackage::class)
                           ->whereIn('auditable_id', $packageIds);
                    });
                }
                
                // LessonStudent logs
                if (!empty($lessonStudentIds)) {
                    $q->orWhere(function ($lsq) use ($lessonStudentIds) {
                        $lsq->where('auditable_type', LessonStudent::class)
                             ->whereIn('auditable_id', $lessonStudentIds);
                    });
                }
                
                // Attendance logs
                if (!empty($attendanceIds)) {
                    $q->orWhere(function ($aq) use ($attendanceIds) {
                        $aq->where('auditable_type', Attendance::class)
                            ->whereIn('auditable_id', $attendanceIds);
                    });
                }

                // Document logs
                if (!empty($documentIds)) {
                    $q->orWhere(function ($dq) use ($documentIds) {
                        $dq->where('auditable_type', StudentDocument::class)
                            ->whereIn('auditable_id', $documentIds);
                    });
                }
            })
            ->select('audit_logs.*', 'users.name as user_name')
            ->get();

        $events = [];
        foreach ($logs as $log) {
            $createdAt = Carbon::parse($log->created_at);
            
            // Map auditable class name to friendly English name
            $typeName = 'Profile';
            if ($log->auditable_type === StudentContract::class) {
                $typeName = 'Contract';
            } elseif ($log->auditable_type === StudentPackage::class) {
                $typeName = 'Package';
            } elseif ($log->auditable_type === LessonStudent::class) {
                $typeName = 'Lesson enrollment';
            } elseif ($log->auditable_type === Attendance::class) {
                $typeName = 'Attendance';
            } elseif ($log->auditable_type === StudentDocument::class) {
                $typeName = 'Document';
            }

            $eventAction = [
                'created' => 'Created',
                'updated' => 'Updated',
                'deleted' => 'Deleted'
            ][$log->event] ?? $log->event;

            $events[] = [
                'id' => 'audit-' . $log->id,
                'type' => 'change',
                'title' => "System event: {$eventAction} ({$typeName})",
                'description' => "User " . ($log->user_name ?? 'System') . " performed {$eventAction} on {$typeName}",
                'date' => $createdAt->format('Y-m-d'),
                'time' => $createdAt->format('H:i'),
                'icon' => 'document-text',
                'color' => 'gray',
                'metadata' => [
                    'audit_id' => $log->id,
                    'event' => $log->event,
                    'auditable_type' => $log->auditable_type,
                    'user' => $log->user_name,
                    'old_values' => json_decode($log->old_values, true),
                    'new_values' => json_decode($log->new_values, true),
                ]
            ];
        }

        return $events;
    }
}
