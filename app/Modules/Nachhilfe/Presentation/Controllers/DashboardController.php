<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentContract;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $user = request()->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin', 'Teacher', 'Receptionist']) && !$user->can('view dashboard'))) {
            abort(403, 'Unauthorized to view dashboard.');
        }

        $today = Carbon::today();
        
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        
        $activeContracts = StudentContract::where('status', 'active')
            ->count();
 
        $lessonsTodayCount = Lesson::whereDate('date', $today)->count();

        $canManageBilling = $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) || $user->can('manage billing');

        $unpaidInvoices = collect();
        $recentUnpaidInvoices = collect();
        $totalRevenueThisMonth = 0.0;
        $pendingBalances = 0.0;
        $revenueChart = [];

        if ($canManageBilling) {
            $unpaidInvoices = Invoice::with(['student'])
                ->whereIn('status', ['draft', 'unpaid'])
                ->limit(5)
                ->get();

            $totalRevenueThisMonth = (float) Invoice::where('status', 'paid')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('total_amount');

            $pendingBalances = (float) Invoice::where('status', 'unpaid')
                ->sum('total_amount');

            $recentUnpaidInvoices = Invoice::with(['student'])
                ->where('status', 'unpaid')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $sum = Invoice::where('status', 'paid')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('total_amount');
                $revenueChart[] = [
                    'month' => $month->format('M Y'),
                    'revenue' => (float) $sum
                ];
            }
        }

        $upcomingLessons = Lesson::with(['teacher', 'room', 'subject', 'students'])
            ->whereDate('date', $today)
            ->whereTime('start_time', '>=', Carbon::now()->format('H:i:s'))
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

        $canSeeTeacherSensitive = $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) || $user->can('manage nachhilfe') || $user->can('manage billing');
        $canSeeStudentSensitive = $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin', 'Receptionist']) || $user->can('manage students');

        $upcomingLessons->transform(function ($lesson) use ($canSeeTeacherSensitive, $canSeeStudentSensitive) {
            if ($lesson->teacher && !$canSeeTeacherSensitive) {
                $lesson->teacher->makeHidden(['hourly_rate']);
            }
            if ($lesson->students && !$canSeeStudentSensitive) {
                $lesson->students->makeHidden([
                    'parent_phone_1',
                    'parent_phone_2',
                    'parent_email',
                    'parent_name',
                    'address',
                    'birth_date',
                    'notes'
                ]);
            }
            return $lesson;
        });

        $currentTime = Carbon::now()->format('H:i:s');
        $liveLessons = Lesson::with(['teacher', 'room', 'subject'])
            ->whereDate('date', $today)
            ->whereTime('start_time', '<=', $currentTime)
            ->whereTime('end_time', '>=', $currentTime)
            ->get();
            
        $activeRoomIds = $liveLessons->pluck('room_id')->filter()->toArray();
        $activeTeacherIds = $liveLessons->pluck('teacher_id')->filter()->toArray();

        $rooms = \App\Modules\Nachhilfe\Infrastructure\Models\Room::all()->map(function ($room) use ($activeRoomIds) {
            $room->is_occupied = in_array($room->id, $activeRoomIds);
            return $room;
        });

        $teachers = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::all()->map(function ($teacher) use ($activeTeacherIds) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'is_teaching' => in_array($teacher->id, $activeTeacherIds),
            ];
        });

        return response()->json([
            'data' => [
                'stats' => [
                    'total_students' => $totalStudents,
                    'total_teachers' => $totalTeachers,
                    'active_packages' => $activeContracts,
                    'lessons_today' => $lessonsTodayCount,
                    'revenue_this_month' => (float) $totalRevenueThisMonth,
                    'pending_balances' => (float) $pendingBalances,
                ],
                'upcoming_lessons' => $upcomingLessons,
                'depleted_packages' => $unpaidInvoices,
                'recent_invoices' => $recentUnpaidInvoices,
                'revenue_chart' => $revenueChart,
                'live_status' => [
                    'rooms' => $rooms,
                    'teachers' => $teachers,
                ]
            ]
        ]);
    }
}
