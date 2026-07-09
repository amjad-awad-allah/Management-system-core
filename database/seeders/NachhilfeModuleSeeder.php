<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class NachhilfeModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create standard subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'German', 'code' => 'DEU'],
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'History', 'code' => 'HIS'],
        ];
        
        foreach ($subjects as $sub) {
            \Illuminate\Support\Facades\DB::table('subjects')->updateOrInsert([
                'name' => $sub['name']
            ], [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'code' => $sub['code'],
                'description' => "Standard {$sub['name']} Course",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create standard packages
        $packages = [
            ['name' => '10 Hours Private', 'description' => '10 Hours of private lessons', 'hours' => 10, 'price' => 150.00],
            ['name' => '20 Hours Private', 'description' => '20 Hours of private lessons', 'hours' => 20, 'price' => 280.00],
            ['name' => 'Jobcenter BuT 50', 'description' => '50 Hours funded by Jobcenter', 'hours' => 50, 'price' => 0.00],
        ];

        $createdPackages = [];
        foreach ($packages as $pkg) {
            $id = (string) \Illuminate\Support\Str::ulid();
            \Illuminate\Support\Facades\DB::table('packages')->insert([
                'id' => $id,
                'name' => $pkg['name'],
                'description' => $pkg['description'],
                'hours' => $pkg['hours'],
                'price' => $pkg['price'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdPackages[] = $id;
        }

        $this->call([
            \Database\Seeders\Modules\Nachhilfe\RoomSeeder::class,
            \Database\Seeders\Modules\Nachhilfe\CancellationPolicySeeder::class,
        ]);
        
        $students = Student::factory()->count(20)->create(); // Reduced to 20 for faster seeding and easier management

        // Create 5 teachers
        $teachers = Teacher::factory()->count(5)->create();

        // Assign random packages to students
        foreach ($students as $student) {
            if (rand(0, 1) === 1) { // 50% chance
                \Illuminate\Support\Facades\DB::table('student_packages')->insert([
                    'id' => (string) \Illuminate\Support\Str::ulid(),
                    'student_id' => $student->id,
                    'package_id' => $createdPackages[array_rand($createdPackages)],
                    'total_hours' => 20,
                    'remaining_hours' => rand(5, 20),
                    'funding_source' => rand(0, 1) === 1 ? 'private' : 'jobcenter',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Get some subjects and rooms
        $subjects = \Illuminate\Support\Facades\DB::table('subjects')->pluck('id')->toArray();
        $rooms = \Illuminate\Support\Facades\DB::table('rooms')->pluck('id')->toArray();

        if (!empty($subjects) && !empty($rooms)) {
            // Create some lessons in the current week (past and future)
            $now = now();
            for ($i = -3; $i <= 3; $i++) { // 3 days ago to 3 days in future
                $lessonDate = $now->copy()->addDays($i)->setTime(rand(14, 18), 0); // between 14:00 and 18:00
                $teacher = $teachers->random();
                
                $lessonId = (string) \Illuminate\Support\Str::ulid();
                \Illuminate\Support\Facades\DB::table('lessons')->insert([
                    'id' => $lessonId,
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subjects[array_rand($subjects)],
                    'room_id' => $rooms[array_rand($rooms)],
                    'type' => rand(0, 1) === 1 ? 'individual' : 'group',
                    'date' => $lessonDate->format('Y-m-d'),
                    'start_time' => $lessonDate->format('H:i:s'),
                    'end_time' => $lessonDate->copy()->addMinutes(60)->format('H:i:s'),
                    'duration_minutes' => 60,
                    'status' => $i < 0 ? 'completed' : 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Enroll 1-3 random students in each lesson
                $enrolledStudents = $students->random(rand(1, 3));
                foreach ($enrolledStudents as $enrolledStudent) {
                    \Illuminate\Support\Facades\DB::table('lesson_students')->insert([
                        'id' => (string) \Illuminate\Support\Str::ulid(),
                        'lesson_id' => $lessonId,
                        'student_id' => $enrolledStudent->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
