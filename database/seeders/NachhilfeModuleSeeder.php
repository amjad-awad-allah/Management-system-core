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
use App\Core\Models\User;
use App\Core\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NachhilfeModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Wipe existing Nachhilfe data (Clean State)
        DB::table('attendances')->delete();
        DB::table('lesson_students')->delete();
        DB::table('lessons')->delete();
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
        $parentRole = Role::firstOrCreate(['name' => 'Student']); // 'Student' role represents parent/student account in auth

        // 3. Create default test users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password')
            ]
        );
        $adminUser->assignRole($adminRole);

        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@teacher.com'],
            [
                'name' => 'Herr Dr. Thomas Müller',
                'password' => Hash::make('password')
            ]
        );
        $teacherUser->assignRole($teacherRole);

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

        // 7. Create Teacher profile linked to teacherUser
        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Herr Dr. Thomas Müller',
            'email' => 'teacher@teacher.com',
            'phone' => '+49 176 12345678',
            'qualification' => 'Staatsexamen Lehramt Gymnasium',
            'hourly_rate' => 30.00
        ]);

        // 8. Create Student profile (Max Mustermann) linked to parentUser
        $student = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $parentUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'birth_date' => '2012-05-15',
            'school' => 'Heinrich-Heine-Gymnasium',
            'grade' => '8',
            'parent_phone_1' => '+49 151 98765432'
        ]);

        // 9. Assign Contracts
        DB::table('student_contracts')->insert([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $createdSubjects['Mathematics'],
            'hours_per_week' => 2,
            'hourly_rate' => 25.00,
            'start_date' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 10. Create Packages / الموافقات وباقات الساعات (BuT Vouchers & Private Packages)
        // Active Mathematics Voucher (Jobcenter)
        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $createdSubjects['Mathematics'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-89304-MATH',
            'total_hours' => 50,
            'remaining_hours' => 35,
            'status' => 'active',
            'expires_at' => now()->addMonths(3)->toDateString()
        ]);

        // Pending German Voucher (Jobcenter)
        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $createdSubjects['German'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-PENDING-DEU',
            'total_hours' => 30,
            'remaining_hours' => 30,
            'status' => 'pending_approval',
            'expires_at' => now()->addMonths(6)->toDateString()
        ]);

        // Expired English Voucher (Jobcenter)
        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $createdSubjects['English'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-EXPIRED-ENG',
            'total_hours' => 20,
            'remaining_hours' => 5,
            'status' => 'expired',
            'expires_at' => now()->subMonth()->toDateString()
        ]);

        // Private Physics Package
        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $createdSubjects['Physics'],
            'funding_source' => 'private',
            'total_hours' => 10,
            'remaining_hours' => 8,
            'status' => 'active',
            'expires_at' => now()->addMonths(2)->toDateString()
        ]);

        // 11. Create Private Student Documents and files in storage
        Storage::put('private/student-documents/mathe_bescheid.pdf', 'dummy PDF content for Mathe approval');
        StudentDocument::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'category' => 'application',
            'title' => 'Bewilligungsbescheid Mathe Q3',
            'file_path' => 'private/student-documents/mathe_bescheid.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1500,
            'uploaded_by' => $adminUser->id,
            'document_date' => '2026-07-01',
            'expires_at' => now()->addMonths(3)->toDateString()
        ]);

        Storage::put('private/student-documents/schulvertrag.pdf', 'dummy PDF content for contract');
        StudentDocument::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'category' => 'contract',
            'title' => 'Schulvertrag SmartDirex',
            'file_path' => 'private/student-documents/schulvertrag.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2400,
            'uploaded_by' => $adminUser->id,
            'document_date' => '2026-07-01'
        ]);

        Storage::put('private/student-documents/stundennachweis_juni.pdf', 'dummy PDF content for attendance sheet');
        StudentDocument::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'category' => 'attendance_sheet',
            'title' => 'Stundennachweis Juni 2026',
            'file_path' => 'private/student-documents/stundennachweis_juni.pdf',
            'mime_type' => 'application/pdf',
            'size' => 900,
            'uploaded_by' => $adminUser->id,
            'document_date' => '2026-06-30'
        ]);

        // 12. Create Completed Lessons (Attendance records) for Stundennachweis PDF verification
        // Mathematics lesson 1: Present
        $lesson1 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $createdRooms[0],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => now()->startOfMonth()->addDays(2)->toDateString(), // 3rd of July
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'completed'
        ]);
        $ls1 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson1->id, 'student_id' => $student->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls1->id, 'status' => 'present', 'marked_by' => $teacherUser->id]);

        // Mathematics lesson 2: Late
        $lesson2 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $createdRooms[0],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => now()->startOfMonth()->addDays(9)->toDateString(), // 10th of July
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'completed'
        ]);
        $ls2 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson2->id, 'student_id' => $student->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls2->id, 'status' => 'late', 'marked_by' => $teacherUser->id, 'note' => '10 minutes late']);

        // German lesson: Present
        $lesson3 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $createdRooms[1],
            'subject_id' => $createdSubjects['German'],
            'type' => 'individual',
            'date' => now()->startOfMonth()->addDays(4)->toDateString(), // 5th of July
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'duration_minutes' => 60,
            'status' => 'completed'
        ]);
        $ls3 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson3->id, 'student_id' => $student->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls3->id, 'status' => 'present', 'marked_by' => $teacherUser->id]);

        // English lesson: Excused (should be excluded from billing list)
        $lesson4 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $createdRooms[0],
            'subject_id' => $createdSubjects['English'],
            'type' => 'individual',
            'date' => now()->startOfMonth()->addDays(11)->toDateString(), // 12th of July
            'start_time' => '15:00:00',
            'end_time' => '16:30:00',
            'duration_minutes' => 90,
            'status' => 'completed'
        ]);
        $ls4 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson4->id, 'student_id' => $student->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls4->id, 'status' => 'absent_excused', 'marked_by' => $teacherUser->id]);

        // Upcoming Lesson (scheduled)
        $lessonUpcoming = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $createdRooms[2],
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => now()->addDays(5)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lessonUpcoming->id, 'student_id' => $student->id]);

        // 13. Seed 10 other random students for general view/table variety
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

            // Assign Math Voucher
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
