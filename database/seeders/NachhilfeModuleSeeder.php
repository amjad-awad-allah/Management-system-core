<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentDocument;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use App\Core\Models\User;
use App\Core\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NachhilfeModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Wipe existing Nachhilfe data (Clean State)
        DB::table('attendances')->delete();
        DB::table('lesson_students')->delete();
        DB::table('lessons')->delete();
        DB::table('holidays')->delete();
        DB::table('student_contracts')->delete();
        DB::table('student_documents')->delete();
        DB::table('student_packages')->delete();
        DB::table('students')->delete();
        DB::table('teachers')->delete();
        DB::table('packages')->delete();
        DB::table('rooms')->delete();
        DB::table('subjects')->delete();
        DB::table('cancellation_policies')->delete();

        // 2. Ensure Roles are present
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $parentRole = Role::firstOrCreate(['name' => 'Student']);

        // 3. Create default test users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password')
            ]
        );
        $adminUser->assignRole($adminRole);

        $teacherUser1 = User::firstOrCreate(
            ['email' => 'teacher@teacher.com'],
            [
                'name' => 'Herr Dr. Thomas Müller',
                'password' => Hash::make('password')
            ]
        );
        $teacherUser1->assignRole($teacherRole);

        $teacherUser2 = User::firstOrCreate(
            ['email' => 'sarah.weber@teacher.com'],
            [
                'name' => 'Frau Sarah Weber',
                'password' => Hash::make('password')
            ]
        );
        $teacherUser2->assignRole($teacherRole);

        $teacherUser3 = User::firstOrCreate(
            ['email' => 'michael.becker@teacher.com'],
            [
                'name' => 'Herr Michael Becker',
                'password' => Hash::make('password')
            ]
        );
        $teacherUser3->assignRole($teacherRole);

        $parentUser = User::firstOrCreate(
            ['email' => 'parent@parent.com'],
            [
                'name' => 'Erika Mustermann',
                'password' => Hash::make('password')
            ]
        );
        $parentUser->assignRole($parentRole);

        // 4. Create Subjects
        $subjects = [
            'Mathematics' => 'MATH',
            'English' => 'ENG',
            'German' => 'DEU',
            'Physics' => 'PHY',
            'Chemistry' => 'CHE',
        ];
        $createdSubjects = [];
        foreach ($subjects as $name => $code) {
            $id = (string) Str::ulid();
            DB::table('subjects')->insert([
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'description' => "Standard {$name} tutoring course.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $createdSubjects[$name] = $id;
        }

        // 5. Call child seeders for Rooms and Cancellation Policies
        $this->call([
            \Database\Seeders\Modules\Nachhilfe\RoomSeeder::class,
            \Database\Seeders\Modules\Nachhilfe\CancellationPolicySeeder::class,
        ]);
        $createdRooms = DB::table('rooms')->pluck('id')->toArray();

        // 6. Create Teachers
        $teacher1 = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser1->id,
            'name' => 'Herr Dr. Thomas Müller',
            'email' => 'teacher@teacher.com',
            'phone' => '+49 176 12345678',
            'qualification' => 'Staatsexamen Lehramt Gymnasium (Mathe/Physik)',
            'hourly_rate' => 35.00
        ]);

        $teacher2 = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser2->id,
            'name' => 'Frau Sarah Weber',
            'email' => 'sarah.weber@teacher.com',
            'phone' => '+49 176 87654321',
            'qualification' => 'Master of Education (Deutsch/Englisch)',
            'hourly_rate' => 30.00
        ]);

        $teacher3 = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser3->id,
            'name' => 'Herr Michael Becker',
            'email' => 'michael.becker@teacher.com',
            'phone' => '+49 176 99887766',
            'qualification' => 'Diplom-Biologe / Chemie-Dozent',
            'hourly_rate' => 32.00
        ]);

        // 7. Create Primary Student (Max Mustermann)
        $studentMax = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $parentUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'birth_date' => '2012-05-15',
            'school' => 'Heinrich-Heine-Gymnasium',
            'grade' => '8',
            'parent_phone_1' => '+49 151 98765432'
        ]);

        // Additional named students
        $studentEmma = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Emma',
            'last_name' => 'Fischer',
            'birth_date' => '2013-03-20',
            'school' => 'Goethe-Gymnasium',
            'grade' => '7',
            'parent_phone_1' => '+49 171 11223344'
        ]);

        $studentLukas = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Lukas',
            'last_name' => 'Wagner',
            'birth_date' => '2011-09-10',
            'school' => 'Schiller-Realschule',
            'grade' => '9',
            'parent_phone_1' => '+49 172 55667788'
        ]);

        // 8. Assign Packages to Students
        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $studentMax->id,
            'subject_id' => $createdSubjects['Mathematics'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-89304-MATH',
            'total_hours' => 50,
            'remaining_hours' => 35,
            'status' => 'active',
            'expires_at' => now()->addMonths(3)->toDateString()
        ]);

        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $studentEmma->id,
            'subject_id' => $createdSubjects['German'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-EMMA-DEU',
            'total_hours' => 30,
            'remaining_hours' => 24,
            'status' => 'active',
            'expires_at' => now()->addMonths(4)->toDateString()
        ]);

        // 9. Seed Public Holidays & School Vacations in NRW
        Holiday::create([
            'id' => (string) Str::ulid(),
            'source' => 'custom',
            'type' => 'public',
            'name' => 'Tag der Deutschen Einheit',
            'start_date' => '2026-10-03',
            'end_date' => '2026-10-03',
            'state' => null,
            'is_active' => true,
        ]);

        Holiday::create([
            'id' => (string) Str::ulid(),
            'source' => 'custom',
            'type' => 'school',
            'name' => 'Sommerferien NRW',
            'start_date' => '2026-07-20',
            'end_date' => '2026-09-01',
            'state' => 'NW',
            'is_active' => true,
        ]);

        // 10. Seed Current Week Lessons for Calendar Display
        $now = Carbon::now('Europe/Berlin');
        $monday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $tuesday = $monday->copy()->addDay();
        $wednesday = $monday->copy()->addDays(2);
        $thursday = $monday->copy()->addDays(3);
        $friday = $monday->copy()->addDays(4);

        // Lesson 1: Monday 09:00 - 10:30 (Math, Raum 1, Herr Müller, Completed)
        $l1 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'room_id' => $createdRooms[0],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => $monday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'duration_minutes' => 90,
            'status' => 'completed',
            'notes' => 'Gleichungssysteme wiederholt.'
        ]);
        $ls1 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l1->id, 'student_id' => $studentMax->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls1->id, 'status' => 'present', 'marked_by' => $teacherUser1->id]);

        // Lesson 2: Monday 11:00 - 12:30 (German, Raum 2, Frau Weber, Scheduled)
        $l2 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher2->id,
            'room_id' => $createdRooms[1],
            'subject_id' => $createdSubjects['German'],
            'type' => 'individual',
            'date' => $monday->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Textanalyse Übung.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l2->id, 'student_id' => $studentEmma->id]);

        // Lesson 3: Tuesday 10:00 - 11:30 (Physics, Raum 1, Herr Müller, Scheduled)
        $l3 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'room_id' => $createdRooms[0],
            'subject_id' => $createdSubjects['Physics'],
            'type' => 'individual',
            'date' => $tuesday->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Mechanik und Newtonsche Gesetze.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l3->id, 'student_id' => $studentMax->id]);

        // Lesson 4: Tuesday 14:00 - 15:30 (English, Online, Frau Weber, Scheduled)
        $l4 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher2->id,
            'room_id' => $createdRooms[3],
            'subject_id' => $createdSubjects['English'],
            'type' => 'individual',
            'date' => $tuesday->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Grammatik: Present Perfect vs Past Simple.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l4->id, 'student_id' => $studentLukas->id]);

        // Lesson 5: Wednesday 09:00 - 10:30 (Math, Raum 3, Herr Müller, Scheduled Group)
        $l5 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'room_id' => $createdRooms[2],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'group',
            'date' => $wednesday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Gruppenunterricht Geometrie.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l5->id, 'student_id' => $studentMax->id]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l5->id, 'student_id' => $studentEmma->id]);

        // Lesson 6: Thursday 14:00 - 15:30 (Chemistry, Raum 2, Herr Becker, Scheduled)
        $l6 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher3->id,
            'room_id' => $createdRooms[1],
            'subject_id' => $createdSubjects['Chemistry'],
            'type' => 'individual',
            'date' => $thursday->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Organische Chemie.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l6->id, 'student_id' => $studentLukas->id]);

        // Lesson 7: Friday 16:00 - 17:30 (Math, Online, Herr Becker, Scheduled)
        $l7 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher3->id,
            'room_id' => $createdRooms[3],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => $friday->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '17:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Vorbereitung Klassenarbeit.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l7->id, 'student_id' => $studentMax->id]);

        // 11. Seed 10 other random students
        for ($i = 0; $i < 10; $i++) {
            $randUser = User::create([
                'name' => fake()->name(),
                'email' => "student{$i}@test.com",
                'password' => Hash::make('password')
            ]);
            $randUser->assignRole($parentRole);

            $randStudent = Student::create([
                'id' => (string) Str::ulid(),
                'user_id' => $randUser->id,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'birth_date' => fake()->date('Y-m-d', '-10 years'),
                'school' => 'Realschule ' . fake()->city(),
                'grade' => (string)rand(5, 10),
                'parent_phone_1' => fake()->phoneNumber()
            ]);

            StudentPackage::create([
                'id' => (string) Str::ulid(),
                'student_id' => $randStudent->id,
                'subject_id' => $createdSubjects['Mathematics'],
                'funding_source' => 'jobcenter',
                'voucher_reference' => 'JC-REF-' . rand(10000, 99999),
                'total_hours' => 30,
                'remaining_hours' => 25,
                'status' => 'active',
                'expires_at' => now()->addMonths(4)->toDateString()
            ]);
        }
    }
}
