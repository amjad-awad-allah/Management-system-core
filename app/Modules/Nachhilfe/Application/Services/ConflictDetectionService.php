<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    private string $timezone = 'Europe/Berlin';

    /**
     * Analyze a collection of lessons for a date range and detect teacher, room, and student overlaps.
     *
     * @param Collection<int, Lesson> $lessons
     * @return array<string, array<string, mixed>> Keyed by lesson_id
     */
    public function detectConflicts(Collection $lessons): array
    {
        // Exclude cancelled lessons from conflict detection
        $activeLessons = $lessons->filter(fn(Lesson $l) => $l->status !== 'cancelled')->values();
        
        $conflictMap = [];

        foreach ($activeLessons as $i => $lessonA) {
            $startA = Carbon::parse($lessonA->date . ' ' . $lessonA->start_time, $this->timezone);
            $endA = Carbon::parse($lessonA->date . ' ' . $lessonA->end_time, $this->timezone);

            if (!isset($conflictMap[$lessonA->id])) {
                $conflictMap[$lessonA->id] = [
                    'has_conflict' => false,
                    'conflict_types' => [],
                    'conflict_details' => [],
                ];
            }

            foreach ($activeLessons as $j => $lessonB) {
                if ($i >= $j) {
                    continue; // Avoid self-comparison and duplicate checks
                }

                $startB = Carbon::parse($lessonB->date . ' ' . $lessonB->start_time, $this->timezone);
                $endB = Carbon::parse($lessonB->date . ' ' . $lessonB->end_time, $this->timezone);

                // Explicit Mathematical Overlap Formula: new_start < existing_end AND new_end > existing_start
                $isOverlapping = ($startA->lt($endB) && $endA->gt($startB));

                if (!$isOverlapping) {
                    continue;
                }

                if (!isset($conflictMap[$lessonB->id])) {
                    $conflictMap[$lessonB->id] = [
                        'has_conflict' => false,
                        'conflict_types' => [],
                        'conflict_details' => [],
                    ];
                }

                // Check 1: Teacher Overlap
                if ($lessonA->teacher_id && $lessonA->teacher_id === $lessonB->teacher_id) {
                    $this->addConflict($conflictMap, $lessonA->id, $lessonB->id, 'teacher', 
                        "Teacher is double-booked between {$startA->format('H:i')}-{$endA->format('H:i')} and {$startB->format('H:i')}-{$endB->format('H:i')}");
                }

                // Check 2: Room Overlap
                if ($lessonA->room_id && $lessonA->room_id === $lessonB->room_id) {
                    $this->addConflict($conflictMap, $lessonA->id, $lessonB->id, 'room', 
                        "Room is double-booked between {$startA->format('H:i')}-{$endA->format('H:i')} and {$startB->format('H:i')}-{$endB->format('H:i')}");
                }

                // Check 3: Student Overlap
                $studentsA = $lessonA->students->pluck('id')->toArray();
                $studentsB = $lessonB->students->pluck('id')->toArray();
                $overlappingStudents = array_intersect($studentsA, $studentsB);

                if (!empty($overlappingStudents)) {
                    $this->addConflict($conflictMap, $lessonA->id, $lessonB->id, 'student', 
                        "Student(s) enrolled in multiple overlapping lessons at {$startA->format('H:i')}-{$endA->format('H:i')}");
                }
            }
        }

        return $conflictMap;
    }

    /**
     * Helper to check if a specific new/rescheduled time slot conflicts with existing active lessons.
     */
    public function findConflictsForSlot(
        string $date,
        string $startTime,
        string $endTime,
        ?string $teacherId,
        ?string $roomId,
        array $studentIds,
        ?string $excludeLessonId = null
    ): array {
        $startNew = Carbon::parse("{$date} {$startTime}", $this->timezone);
        $endNew = Carbon::parse("{$date} {$endTime}", $this->timezone);

        $query = Lesson::with('students')
            ->where('date', $date)
            ->where('status', '!=', 'cancelled');

        if ($excludeLessonId) {
            $query->where('id', '!=', $excludeLessonId);
        }

        $existingLessons = $query->get();
        $conflicts = [];

        foreach ($existingLessons as $existing) {
            $startExisting = Carbon::parse("{$existing->date} {$existing->start_time}", $this->timezone);
            $endExisting = Carbon::parse("{$existing->date} {$existing->end_time}", $this->timezone);

            // Mathematical Overlap check
            if ($startNew->lt($endExisting) && $endNew->gt($startExisting)) {
                if ($teacherId && $existing->teacher_id === $teacherId) {
                    $conflicts[] = [
                        'type' => 'teacher',
                        'lesson_id' => $existing->id,
                        'message' => "Teacher is already scheduled for another lesson during {$startExisting->format('H:i')}-{$endExisting->format('H:i')}.",
                    ];
                }

                if ($roomId && $existing->room_id === $roomId) {
                    $conflicts[] = [
                        'type' => 'room',
                        'lesson_id' => $existing->id,
                        'message' => "Room is already reserved for another lesson during {$startExisting->format('H:i')}-{$endExisting->format('H:i')}.",
                    ];
                }

                $existingStudents = $existing->students->pluck('id')->toArray();
                if (!empty(array_intersect($studentIds, $existingStudents))) {
                    $conflicts[] = [
                        'type' => 'student',
                        'lesson_id' => $existing->id,
                        'message' => "One or more students are already enrolled in an overlapping lesson.",
                    ];
                }
            }
        }

        return $conflicts;
    }

    private function addConflict(array &$conflictMap, string $idA, string $idB, string $type, string $message): void
    {
        $conflictMap[$idA]['has_conflict'] = true;
        if (!in_array($type, $conflictMap[$idA]['conflict_types'], true)) {
            $conflictMap[$idA]['conflict_types'][] = $type;
        }
        $conflictMap[$idA]['conflict_details'][] = ['related_lesson_id' => $idB, 'type' => $type, 'message' => $message];

        $conflictMap[$idB]['has_conflict'] = true;
        if (!in_array($type, $conflictMap[$idB]['conflict_types'], true)) {
            $conflictMap[$idB]['conflict_types'][] = $type;
        }
        $conflictMap[$idB]['conflict_details'][] = ['related_lesson_id' => $idA, 'type' => $type, 'message' => $message];
    }
}
