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

        $lessonStudent = LessonStudent::findOrFail($lessonStudentId);

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
