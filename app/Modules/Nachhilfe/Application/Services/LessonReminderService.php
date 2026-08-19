<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Core\Notification\Services\NotificationService;
use App\Modules\Nachhilfe\Application\Queries\ReminderCandidateQuery;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\CarbonImmutable;

class LessonReminderService
{
    public function __construct(
        private readonly ReminderCandidateQuery $candidateQuery,
        private readonly ReminderRecipientResolver $recipientResolver,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Process automated lesson reminders for 24h and 2h sliding windows.
     *
     * @param CarbonImmutable|null $referenceNow
     * @return array{lessons_24h: int, enqueued_24h: int, lessons_2h: int, enqueued_2h: int}
     */
    public function process(?CarbonImmutable $referenceNow = null): array
    {
        $stats24h = $this->processWindow('24h', 'lesson_reminder_24h', $referenceNow);
        $stats2h = $this->processWindow('2h', 'lesson_reminder_2h', $referenceNow);

        return [
            'lessons_24h' => $stats24h['lessons'],
            'enqueued_24h' => $stats24h['enqueued'],
            'lessons_2h' => $stats2h['lessons'],
            'enqueued_2h' => $stats2h['enqueued'],
        ];
    }

    /**
     * Process a specific reminder window.
     *
     * @param string $window '24h' | '2h'
     * @param string $notificationType 'lesson_reminder_24h' | 'lesson_reminder_2h'
     * @param CarbonImmutable|null $referenceNow
     * @return array{lessons: int, enqueued: int}
     */
    private function processWindow(string $window, string $notificationType, ?CarbonImmutable $referenceNow): array
    {
        $candidates = $this->candidateQuery->getCandidatesForWindow($window, $referenceNow);
        $enqueuedCount = 0;

        foreach ($candidates as $lesson) {
            $recipients = $this->recipientResolver->resolve($lesson, $notificationType);

            foreach ($recipients as $recipient) {
                $user = $recipient['user'];
                $channels = $recipient['channels'];
                $role = $recipient['role'];
                $studentName = $recipient['student_name'] ?? ($lesson->students->first()?->first_name ?? '');

                $startTimeFormatted = substr($lesson->start_time, 0, 5);
                $endTimeFormatted = substr($lesson->end_time, 0, 5);
                $subjectName = $lesson->subject?->name ?? 'Unterricht';
                $roomName = $lesson->room?->name ?? 'Standardraum';
                $teacherName = $lesson->teacher?->name ?? 'Lehrkraft';

                $title = $window === '24h'
                    ? 'Unterrichts-Erinnerung (in 24 Stunden)'
                    : 'Unterrichts-Erinnerung (in 2 Stunden)';

                $timePhrase = $window === '24h' ? 'morgen' : 'in Kürze';
                $message = "Erinnerung: Ihr Unterricht ({$subjectName}) findet {$timePhrase} um {$startTimeFormatted} Uhr (Raum: {$roomName}) statt.";

                $payload = [
                    'schema_version' => 1,
                    'notification_type' => $notificationType,
                    'template' => 'lesson_reminder_default',
                    'template_version' => 1,
                    'locale' => $user->locale ?? 'de',
                    'data' => [
                        'lesson_id' => $lesson->id,
                        'reminder_window' => $window,
                        'recipient_role' => $role,
                        'subject' => $subjectName,
                        'date' => $lesson->date,
                        'start_time' => $startTimeFormatted,
                        'end_time' => $endTimeFormatted,
                        'room' => $roomName,
                        'teacher_name' => $teacherName,
                        'student_name' => $studentName,
                        'title' => $title,
                        'message' => $message,
                    ],
                ];

                $this->notificationService->enqueue(
                    eventType: 'lesson',
                    eventId: $lesson->id,
                    notificationType: $notificationType,
                    recipientUserId: $user->id,
                    channels: $channels,
                    payload: $payload
                );

                $enqueuedCount++;
            }
        }

        return [
            'lessons' => $candidates->count(),
            'enqueued' => $enqueuedCount,
        ];
    }
}
