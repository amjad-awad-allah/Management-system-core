<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $packageIds = StudentPackage::where('student_id', $student->id)->pluck('id')->toArray();
        if (empty($packageIds)) {
            return [];
        }

        // Query notifications table
        $notifications = DB::table('notifications')
            ->where('source_type', 'student_package')
            ->whereIn('source_id', $packageIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by notification_group_id
        $grouped = $notifications->groupBy('notification_group_id');

        $events = [];
        foreach ($grouped as $groupId => $items) {
            $first = $items->first();
            $createdAt = Carbon::parse($first->created_at);

            // Compile collapsed channel statuses
            $statuses = [];
            foreach ($items as $item) {
                $channelName = ucfirst($item->delivery_channel);
                $statusSymbol = $item->delivery_status === 'sent' ? '✓' : '✕';
                $statuses[] = "{$statusSymbol} {$channelName}";
            }
            $statusStr = implode(', ', $statuses);

            $events[] = [
                'id' => 'notification-' . $groupId,
                'type' => 'notification',
                'title' => $first->title,
                'description' => "{$first->message} (Status: {$statusStr})",
                'date' => $createdAt->format('Y-m-d'),
                'time' => $createdAt->format('H:i'),
                'icon' => 'bell',
                'color' => 'yellow',
                'metadata' => [
                    'notification_group_id' => $groupId,
                    'type' => $first->type,
                    'deliveries' => $items->map(fn($item) => [
                        'channel' => $item->delivery_channel,
                        'status' => $item->delivery_status,
                        'external_id' => $item->external_id,
                    ])->toArray(),
                ]
            ];
        }

        return $events;
    }
}
