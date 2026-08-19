<?php

namespace App\Modules\Nachhilfe\Infrastructure\Policies;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class PdfReportPolicy
{
    public function exportPayroll(User $user, ?TeacherPayroll $payroll = null): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Center Manager') || $user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    public function exportTeacherTimetable(User $user, string $teacherId): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Center Manager') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            return $teacher && $teacher->id === $teacherId;
        }

        return false;
    }

    public function exportRoomDoorSheet(User $user): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Center Manager') || $user->hasRole('admin') || $user->hasRole('Teacher')) {
            return true;
        }

        return false;
    }
}
