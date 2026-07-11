<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentContract;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use Illuminate\Http\Request;

class StudentMobileController
{
    public function lessons(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Student')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // A user might be a parent with multiple students, or a student.
        // We will fetch lessons for all students associated with this user_id.
        $studentIds = Student::where('user_id', $user->id)->pluck('id');

        if ($studentIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $query = Lesson::with(['room', 'subject', 'teacher'])
            ->whereHas('students', function ($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            })
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

    public function subscriptions(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Student')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $studentIds = Student::where('user_id', $user->id)->pluck('id');

        if ($studentIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $subscriptions = StudentContract::with(['subject'])
            ->whereIn('student_id', $studentIds)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return response()->json($subscriptions);
    }

    public function invoices(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Student')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ideally invoices should be linked to user_id or student_id.
        // Assuming invoices have student_id.
        $studentIds = Student::where('user_id', $user->id)->pluck('id');

        if ($studentIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $invoices = Invoice::whereIn('student_id', $studentIds)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return response()->json($invoices);
    }
}
