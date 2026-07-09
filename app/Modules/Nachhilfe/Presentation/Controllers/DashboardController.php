<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        
        $activePackages = StudentPackage::where('status', 'active')
            ->where('remaining_hours', '>', 0)
            ->count();

        $lessonsToday = Lesson::whereDate('date', Carbon::today())->count();

        return response()->json([
            'data' => [
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'active_packages' => $activePackages,
                'lessons_today' => $lessonsToday,
            ]
        ]);
    }
}
