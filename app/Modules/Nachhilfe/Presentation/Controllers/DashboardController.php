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
        $today = Carbon::today();
        
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        
        $activeContracts = StudentContract::where('status', 'active')
            ->count();
 
        $lessonsTodayCount = Lesson::whereDate('date', $today)->count();
        
        $unpaidInvoices = Invoice::with(['student'])
            ->whereIn('status', ['draft', 'unpaid'])
            ->limit(5)
            ->get();

        $upcomingLessons = Lesson::with(['teacher', 'room', 'subject', 'students'])
            ->whereDate('date', $today)
            ->whereTime('start_time', '>=', Carbon::now()->format('H:i:s'))
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();

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
            $teacher->is_teaching = in_array($teacher->id, $activeTeacherIds);
            return $teacher;
        });

        $totalRevenueThisMonth = Invoice::where('status', 'paid')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');
            
        $pendingBalances = Invoice::where('status', 'unpaid')
            ->sum('total_amount');
            
        $recentUnpaidInvoices = Invoice::with(['student'])
            ->where('status', 'unpaid')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
 
        $revenueChart = [];
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
