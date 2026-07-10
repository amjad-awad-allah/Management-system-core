<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Nachhilfe\Domain\Services\ConflictCheckerService;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ConflictCheckerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_conflict_checker_prevents_overlaps()
    {
        $teacher = Teacher::create(['id' => (string) Str::ulid(), 'name' => 'John Doe']);
        
        \App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'day_of_week' => \Carbon\Carbon::parse('2026-07-10')->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00'
        ]);

        $room = Room::create(['id' => (string) Str::ulid(), 'name' => 'Room 1', 'capacity' => 10]);
        $subject = SubjectModel::create(['id' => (string) Str::ulid(), 'name' => 'Math']);

        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => '2026-07-10',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $checker = new ConflictCheckerService();

        // No conflict, different time
        $checker->checkConflicts($teacher->id, $room->id, '2026-07-10', '11:00', '12:00');
        $this->assertTrue(true); // Should not throw

        // Conflict: starts before end, ends after start
        $this->expectException(\Exception::class);
        $checker->checkConflicts($teacher->id, $room->id, '2026-07-10', '10:30', '11:30');
    }
}
