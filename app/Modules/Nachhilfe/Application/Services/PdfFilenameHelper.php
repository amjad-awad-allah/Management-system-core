<?php

namespace App\Modules\Nachhilfe\Application\Services;

use Illuminate\Support\Str;

class PdfFilenameHelper
{
    public static function slugify(string $name): string
    {
        $ascii = Str::ascii($name);
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $ascii);
        return trim($slug ?? '', '_');
    }

    public static function teacherTimetable(string $teacherName, string $startDate): string
    {
        $slug = self::slugify($teacherName);
        return "teacher_timetable_{$slug}_{$startDate}.pdf";
    }

    public static function roomDoorSheet(string $roomName, string $date): string
    {
        $slug = self::slugify($roomName);
        return "room_door_sheet_{$slug}_{$date}.pdf";
    }

    public static function payrollReport(string $teacherName, string $yearMonth): string
    {
        $slug = self::slugify($teacherName);
        return "payroll_report_{$slug}_{$yearMonth}.pdf";
    }
}
