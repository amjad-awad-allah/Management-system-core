<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Carbon\Carbon;

class PackageTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $packages = StudentPackage::where('student_id', $student->id)->with('subject')->get();

        $events = [];
        foreach ($packages as $pkg) {
            $createdAt = Carbon::parse($pkg->created_at);
            $subjectName = $pkg->subject?->name ?? 'All subjects';

            $events[] = [
                'id' => 'package-' . $pkg->id,
                'type' => 'package',
                'title' => 'New package purchased',
                'description' => "Package of {$pkg->total_hours} hours for {$subjectName} - Funded by: " . ($pkg->funding_source === 'private' ? 'private' : 'Jobcenter BuT'),
                'date' => $createdAt->format('Y-m-d'),
                'time' => $createdAt->format('H:i'),
                'icon' => 'archive',
                'color' => 'blue',
                'metadata' => [
                    'package_id' => $pkg->id,
                    'total_hours' => $pkg->total_hours,
                    'remaining_hours' => $pkg->remaining_hours,
                    'funding_source' => $pkg->funding_source,
                    'status' => $pkg->status,
                ]
            ];
        }

        return $events;
    }
}
