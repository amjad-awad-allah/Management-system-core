<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel as Subject;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = Teacher::all();
        $subjects = Subject::all();
        $rooms = Room::all();
        $students = Student::all();

        if ($teachers->isEmpty() || $subjects->isEmpty() || $students->isEmpty()) {
            return;
        }

        // Create 20 lessons over the current week
        for ($i = 0; $i < 20; $i++) {
            $teacher = $teachers->random();
            $subject = $subjects->random();
            $room = $rooms->random();

            // Random date between -3 and +3 days from now
            $date = Carbon::now()->addDays(rand(-3, 3));
            $startTime = clone $date;
            $startTime->setTime(rand(14, 18), rand(0, 1) === 1 ? 30 : 0, 0); // 14:00 to 18:30
            $endTime = (clone $startTime)->addMinutes(45);

            $lessonType = rand(0, 1) === 1 ? 'individual' : 'group';

            $lesson = Lesson::create([
                'id' => (string) Str::ulid(),
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'room_id' => $room->id,
                'type' => $lessonType,
                'date' => $startTime->format('Y-m-d'),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'status' => 'scheduled',
            ]);

            // Assign students
            $numStudents = $lessonType === 'individual' ? 1 : rand(2, 5);
            $selectedStudents = $students->random($numStudents);

            foreach ($selectedStudents as $student) {
                // Find a package for this student to tie the lesson to
                $studentPackage = StudentPackage::where('student_id', $student->id)->where('status', 'active')->first();
                
                LessonStudent::create([
                    'id' => (string) Str::ulid(),
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                    'package_id' => $studentPackage ? $studentPackage->package_id : null,
                    'hours_consumed' => 1,
                    'notes' => null,
                ]);
            }
        }
    }
}
