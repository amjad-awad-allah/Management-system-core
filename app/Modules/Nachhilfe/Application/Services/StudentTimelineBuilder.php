<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Timeline\LessonTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\AttendanceTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\BillingTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\PackageTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\NoteTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\AuditTimelineProvider;
use App\Modules\Nachhilfe\Infrastructure\Timeline\NotificationTimelineProvider;

class StudentTimelineBuilder
{
    private array $providers;

    public function __construct(
        LessonTimelineProvider $lessons,
        AttendanceTimelineProvider $attendance,
        BillingTimelineProvider $billing,
        PackageTimelineProvider $packages,
        NoteTimelineProvider $notes,
        AuditTimelineProvider $audit,
        NotificationTimelineProvider $notification
    ) {
        $this->providers = [
            'lesson' => $lessons,
            'attendance' => $attendance,
            'payment' => $billing,
            'package' => $packages,
            'note' => $notes,
            'change' => $audit,
            'notification' => $notification,
        ];
    }

    public function build(Student $student, $user): array
    {
        $events = [];

        // Determine allowed event types based on user roles
        $allowedTypes = ['lesson', 'attendance', 'package', 'note']; // Default minimum (Teacher)
        
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Owner')) {
            $allowedTypes = ['lesson', 'attendance', 'payment', 'package', 'note', 'change', 'notification'];
        } elseif ($user->hasRole('Student')) {
            $allowedTypes = ['lesson', 'attendance', 'payment', 'package', 'note', 'notification'];
        }

        // Aggregate from providers
        foreach ($this->providers as $type => $provider) {
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }

            try {
                $providerEvents = $provider->getEvents($student);
                $events = array_merge($events, $providerEvents);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Timeline provider {$type} failed: " . $e->getMessage());
            }
        }

        // Sort descending chronologically (date desc, then time desc)
        usort($events, function ($a, $b) {
            $dateCompare = strcmp($b['date'], $a['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp($b['time'], $a['time']);
        });

        return $events;
    }
}
