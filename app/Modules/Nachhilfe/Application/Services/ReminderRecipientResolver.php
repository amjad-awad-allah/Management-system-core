<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Core\Models\User;
use App\Core\Models\NotificationPreference;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class ReminderRecipientResolver
{
    /**
     * Default delivery channels when no explicit preference is set.
     */
    private array $defaultChannels = ['in_app', 'chat', 'whatsapp'];

    /**
     * Resolve valid, active recipients and their preferred channels for a lesson reminder.
     *
     * @param Lesson $lesson
     * @param string $notificationType e.g. 'lesson_reminder_24h' | 'lesson_reminder_2h'
     * @return array<int, array{user: User, role: string, channels: array<string>, student_name: string|null}>
     */
    public function resolve(Lesson $lesson, string $notificationType): array
    {
        $recipients = [];
        $resolvedUserIds = [];

        // 1. Teacher Recipient
        if ($lesson->teacher_id) {
            /** @var Teacher|null $teacher */
            $teacher = $lesson->teacher ?? Teacher::find($lesson->teacher_id);
            if ($teacher && !empty($teacher->user_id)) {
                $teacherUser = User::find($teacher->user_id);
                if ($teacherUser && !isset($resolvedUserIds[$teacherUser->id])) {
                    $channels = $this->resolveChannelsForUser($teacherUser, $notificationType);
                    if (!empty($channels)) {
                        $recipients[] = [
                            'user' => $teacherUser,
                            'role' => 'teacher',
                            'channels' => $channels,
                            'student_name' => null,
                        ];
                        $resolvedUserIds[$teacherUser->id] = true;
                    }
                }
            }
        }

        // 2. Student & Parent Recipients
        $students = $lesson->students;
        foreach ($students as $student) {
            $studentFullName = trim("{$student->first_name} {$student->last_name}");

            // A. Student Direct User Account (if student has own login)
            if (!empty($student->user_id)) {
                $studentUser = User::find($student->user_id);
                if ($studentUser && !isset($resolvedUserIds[$studentUser->id])) {
                    $channels = $this->resolveChannelsForUser($studentUser, $notificationType);
                    if (!empty($channels)) {
                        $recipients[] = [
                            'user' => $studentUser,
                            'role' => 'student',
                            'channels' => $channels,
                            'student_name' => $studentFullName,
                        ];
                        $resolvedUserIds[$studentUser->id] = true;
                    }
                }
            }

            // B. Linked Parent User (via parent_email lookup if distinct from student)
            if (!empty($student->parent_email)) {
                $parentUser = User::where('email', $student->parent_email)->first();
                if ($parentUser && !isset($resolvedUserIds[$parentUser->id])) {
                    $channels = $this->resolveChannelsForUser($parentUser, $notificationType);
                    if (!empty($channels)) {
                        $recipients[] = [
                            'user' => $parentUser,
                            'role' => 'parent',
                            'channels' => $channels,
                            'student_name' => $studentFullName,
                        ];
                        $resolvedUserIds[$parentUser->id] = true;
                    }
                }
            }
        }

        return $recipients;
    }

    /**
     * Determine enabled channels based on user preferences.
     */
    public function resolveChannelsForUser(User $user, string $notificationType): array
    {
        $preferences = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->get()
            ->keyBy('channel');

        if ($preferences->isEmpty()) {
            return $this->defaultChannels;
        }

        $activeChannels = [];
        foreach ($this->defaultChannels as $channel) {
            if (isset($preferences[$channel])) {
                if ($preferences[$channel]->enabled) {
                    $activeChannels[] = $channel;
                }
            } else {
                $activeChannels[] = $channel;
            }
        }

        return $activeChannels;
    }
}
