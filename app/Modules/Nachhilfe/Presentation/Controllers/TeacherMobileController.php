<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Http\Request;

class TeacherMobileController
{
    public function lessons(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Teacher')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();

        $query = Lesson::with(['room', 'subject', 'students'])
            ->where('teacher_id', $teacher->id)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($request->has('date')) {
            $query->where('date', $request->query('date'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json($query->paginate());
    }

    public function markAttendance(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Teacher')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();
        
        $lesson = Lesson::where('teacher_id', $teacher->id)->findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|string|exists:students,id',
            'status' => 'required|in:present,absent_excused,absent_unexcused',
            'notes' => 'nullable|string'
        ]);

        $pivot = \App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent::where('lesson_id', $lesson->id)
            ->where('student_id', $validated['student_id'])
            ->firstOrFail();

        // Check if attendance already exists
        $attendance = \App\Modules\Nachhilfe\Infrastructure\Models\Attendance::firstOrNew([
            'lesson_student_id' => $pivot->id,
        ]);

        $attendance->status = $validated['status'];
        $attendance->note = $validated['notes'] ?? null;
        $attendance->save();

        return response()->json(['message' => 'Attendance marked successfully', 'attendance' => $attendance]);
    }

    public function payrolls(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Teacher')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();

        $payrolls = TeacherPayroll::with('items.lesson.subject')
            ->where('teacher_id', $teacher->id)
            ->orderBy('month', 'desc')
            ->paginate();

        return response()->json($payrolls);
    }
}
