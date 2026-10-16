<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Domain\Events\AttendanceMarkedEvent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function store(Request $request, string $lessonStudentId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent_excused,absent_unexcused,late',
            'note' => 'nullable|string'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $lessonStudent = LessonStudent::with('lesson.teacher')->findOrFail($lessonStudentId);

        $canManage = $user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])
            || $user->can('manage lessons');

        if (!$canManage) {
            $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher || $user->hasRole('Teacher')) {
                $isAssignedTeacher = $teacher && $lessonStudent->lesson?->teacher_id === $teacher->id;
                if (!$isAssignedTeacher) {
                    return response()->json(['message' => 'You are not authorized to mark attendance for this lesson.'], 403);
                }
            } else {
                return response()->json(['message' => 'You are not authorized to mark attendance.'], 403);
            }
        }

        $attendance = Attendance::updateOrCreate(
            ['lesson_student_id' => $lessonStudentId],
            [
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'marked_by' => $request->user()?->id,
                'marked_at' => now(),
            ]
        );

        // Dispatch Event
        AttendanceMarkedEvent::dispatch($attendance);

        return response()->json([
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance
        ], 201);
    }
}
