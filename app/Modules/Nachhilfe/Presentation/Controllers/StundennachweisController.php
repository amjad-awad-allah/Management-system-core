<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Application\Queries\GetStundennachweisDataQuery;
use App\Modules\Nachhilfe\Application\Services\StundennachweisPdfService;
use Illuminate\Http\Request;

class StundennachweisController
{
    public function __construct(
        private readonly GetStundennachweisDataQuery $query,
        private readonly StundennachweisPdfService $pdfService
    ) {}

    public function generate(Request $request, string $studentId)
    {
        $user = auth()->user();

        // 1. Verify access permissions (same strict rules as documents)
        $authorized = false;
        if ($user->hasRole(['Admin', 'Super Admin'])) {
            $authorized = true;
        } elseif ($user->hasRole('Student')) {
            $authorized = Student::where('id', $studentId)->where('user_id', $user->id)->exists();
        } elseif ($user->hasRole('Teacher')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $authorized = LessonStudent::where('student_id', $studentId)
                    ->whereHas('lesson', fn($q) => $q->where('teacher_id', $teacher->id))
                    ->exists();
            }
        }

        if (!$authorized) {
            return response()->json(['message' => 'Unauthorized access to student reports.'], 403);
        }

        $request->validate([
            'month' => 'required|date_format:Y-m',
            'subject_id' => 'nullable|string',
        ]);

        $month = $request->input('month');
        $subjectId = $request->input('subject_id');

        $student = Student::withTrashed()->findOrFail($studentId);
        $attendances = $this->query->execute($studentId, $month, $subjectId);

        $pdfBytes = $this->pdfService->generate($student, $month, $attendances);

        return response($pdfBytes, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="Stundennachweis_' . $student->last_name . '_' . $month . '.pdf"');
    }
}
