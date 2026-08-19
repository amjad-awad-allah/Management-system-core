<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentDocument;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\InvoiceItem;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyQuestion;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyResponse;
use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\UserLoginCode;
use App\Core\Models\AuditLog;
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
        DB::table('survey_responses')->delete();
        DB::table('survey_questions')->delete();
        DB::table('surveys')->delete();
        DB::table('chat_messages')->delete();
        DB::table('chat_participants')->delete();
        DB::table('chat_channels')->delete();
        DB::table('user_login_codes')->delete();
        DB::table('invoice_items')->delete();
        DB::table('invoices')->delete();
        DB::table('teacher_payroll_items')->delete();
        DB::table('teacher_payrolls')->delete();
        DB::table('teacher_availabilities')->delete();
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
        $subjectsData = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'desc' => 'Mathematics algebra, geometry, and calculus.'],
            ['name' => 'English', 'code' => 'ENG', 'desc' => 'English grammar, literature, and conversation.'],
            ['name' => 'German', 'code' => 'DEU', 'desc' => 'German language, essay writing, and comprehension.'],
            ['name' => 'Physics', 'code' => 'PHY', 'desc' => 'Physics mechanics, optics, and thermodynamics.'],
            ['name' => 'Chemistry', 'code' => 'CHE', 'desc' => 'General, inorganic, and organic chemistry.'],
        ];

        $createdSubjects = [];
        foreach ($subjectsData as $s) {
            $id = (string) Str::ulid();
            DB::table('subjects')->insert([
                'id' => $id,
                'name' => $s['name'],
                'code' => $s['code'],
                'description' => $s['desc'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $createdSubjects[$s['name']] = $id;
        }

        // 5. Call child seeders for Rooms and Cancellation Policies
        $this->call([
            \Database\Seeders\Modules\Nachhilfe\RoomSeeder::class,
            \Database\Seeders\Modules\Nachhilfe\CancellationPolicySeeder::class,
        ]);
        $createdRooms = DB::table('rooms')->pluck('id')->toArray();

        // 6. Create Teachers & Availabilities
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

        foreach ([$teacher1, $teacher2, $teacher3] as $t) {
            for ($day = 1; $day <= 5; $day++) {
                TeacherAvailability::create([
                    'teacher_id' => $t->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                ]);
            }
        }

        // 7. Create Students
        $studentMax = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $parentUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'birth_date' => '2012-05-15',
            'school' => 'Heinrich-Heine-Gymnasium',
            'grade' => '8',
            'parent_name' => 'Erika Mustermann',
            'parent_phone_1' => '+49 151 98765432',
        ]);

        $studentEmma = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Emma',
            'last_name' => 'Fischer',
            'birth_date' => '2013-03-20',
            'school' => 'Goethe-Gymnasium',
            'grade' => '7',
            'parent_name' => 'Klaus Fischer',
            'parent_phone_1' => '+49 171 11223344',
        ]);

        $studentLukas = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Lukas',
            'last_name' => 'Wagner',
            'birth_date' => '2011-09-10',
            'school' => 'Schiller-Realschule',
            'grade' => '9',
            'parent_name' => 'Monika Wagner',
            'parent_phone_1' => '+49 172 55667788',
        ]);

        $studentSophie = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Sophie',
            'last_name' => 'Schneider',
            'birth_date' => '2014-01-18',
            'school' => 'Humboldt-Gymnasium',
            'grade' => '6',
            'parent_name' => 'Thomas Schneider',
            'parent_phone_1' => '+49 173 99887711',
        ]);

        // 8. Assign Packages & Low Hour Packages
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
            'funding_source' => 'self_pay',
            'voucher_reference' => 'DIR-EMMA-DEU',
            'total_hours' => 30,
            'remaining_hours' => 1.5, // Low balance alert test (< 2h)
            'status' => 'active',
            'expires_at' => now()->addMonths(2)->toDateString()
        ]);

        StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $studentLukas->id,
            'subject_id' => $createdSubjects['Chemistry'],
            'funding_source' => 'jobcenter',
            'voucher_reference' => 'JC-LUKAS-CHE',
            'total_hours' => 40,
            'remaining_hours' => 28,
            'status' => 'active',
            'expires_at' => now()->addMonths(4)->toDateString()
        ]);

        // 9. Seed Public Holidays
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
            'name' => 'Herbstferien NRW',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-24',
            'state' => 'NW',
            'is_active' => true,
        ]);

        // 10. Seed Multi-Status Lessons
        $now = Carbon::now('Europe/Berlin');
        $monday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $tuesday = $monday->copy()->addDay();
        $wednesday = $monday->copy()->addDays(2);
        $thursday = $monday->copy()->addDays(3);
        $friday = $monday->copy()->addDays(4);

        // Lesson 1: Monday Math (Herr Müller, Completed)
        $l1 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'room_id' => $createdRooms[0] ?? (string) Str::ulid(),
            'subject_id' => $createdSubjects['Mathematics'],
            'type' => 'individual',
            'date' => $monday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'duration_minutes' => 90,
            'status' => 'completed',
            'notes' => 'Quadratic functions and systems of equations completed.'
        ]);
        $ls1 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l1->id, 'student_id' => $studentMax->id]);
        Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls1->id, 'status' => 'present', 'marked_by' => $teacherUser1->id]);

        // Lesson 2: Monday German (Frau Weber, Scheduled)
        $l2 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher2->id,
            'room_id' => $createdRooms[1] ?? (string) Str::ulid(),
            'subject_id' => $createdSubjects['German'],
            'type' => 'individual',
            'date' => $monday->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'German essay analysis & grammar training.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l2->id, 'student_id' => $studentEmma->id]);

        // Lesson 3: Tuesday Physics (Herr Müller, Scheduled)
        $l3 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'room_id' => $createdRooms[0] ?? (string) Str::ulid(),
            'subject_id' => $createdSubjects['Physics'],
            'type' => 'individual',
            'date' => $tuesday->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Mechanics & Newton physics laws experiments.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l3->id, 'student_id' => $studentMax->id]);

        // Lesson 4: Tuesday English (Frau Weber, Scheduled Group)
        $l4 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher2->id,
            'room_id' => $createdRooms[2] ?? (string) Str::ulid(),
            'subject_id' => $createdSubjects['English'],
            'type' => 'group',
            'date' => $tuesday->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'scheduled',
            'notes' => 'Grammar review: Present Perfect vs Past Simple.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l4->id, 'student_id' => $studentLukas->id]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l4->id, 'student_id' => $studentSophie->id]);

        // Lesson 5: Wednesday Chemistry (Herr Becker, Cancelled)
        $l5 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher3->id,
            'room_id' => $createdRooms[1] ?? (string) Str::ulid(),
            'subject_id' => $createdSubjects['Chemistry'],
            'type' => 'individual',
            'date' => $wednesday->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'cancelled',
            'notes' => 'Student excused with medical notice 48h prior.'
        ]);
        LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $l5->id, 'student_id' => $studentLukas->id]);

        // 11. Seed Additional Students
        for ($i = 1; $i <= 10; $i++) {
            $randUser = User::firstOrCreate(
                ['email' => "student{$i}@test.com"],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('password')
                ]
            );
            $randUser->assignRole($parentRole);

            $randStudent = Student::create([
                'id' => (string) Str::ulid(),
                'user_id' => $randUser->id,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'birth_date' => fake()->date('Y-m-d', '-12 years'),
                'school' => 'Realschule ' . fake()->city(),
                'grade' => (string)rand(5, 11),
                'parent_name' => fake()->name(),
                'parent_phone_1' => fake()->phoneNumber(),
            ]);

            StudentPackage::create([
                'id' => (string) Str::ulid(),
                'student_id' => $randStudent->id,
                'subject_id' => $createdSubjects['Mathematics'],
                'funding_source' => $i % 2 === 0 ? 'jobcenter' : 'self_pay',
                'voucher_reference' => 'REF-' . rand(10000, 99999),
                'total_hours' => 30,
                'remaining_hours' => rand(5, 25),
                'status' => 'active',
                'expires_at' => now()->addMonths(4)->toDateString()
            ]);
        }

        // 12. Seed Teacher Payrolls
        $payroll1 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'month' => now()->subMonth()->format('Y-m'),
            'total_completed_lessons' => 14,
            'total_hours' => 21.0,
            'total_amount' => 735.00,
            'status' => 'Paid',
            'approved_by' => $adminUser->id,
            'approved_at' => now()->subDays(10),
            'snapshot_hash' => hash('sha256', "payroll-{$teacher1->id}-paid"),
            'snapshot_hash_version' => 1,
        ]);

        $payroll2 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher2->id,
            'month' => now()->subMonth()->format('Y-m'),
            'total_completed_lessons' => 10,
            'total_hours' => 15.0,
            'total_amount' => 450.00,
            'status' => 'Processing',
            'approved_by' => $adminUser->id,
            'approved_at' => now()->subDays(5),
            'snapshot_hash' => hash('sha256', "payroll-{$teacher2->id}-approved"),
            'snapshot_hash_version' => 1,
        ]);

        $payroll3 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher1->id,
            'month' => now()->format('Y-m'),
            'total_completed_lessons' => 4,
            'total_hours' => 6.0,
            'total_amount' => 210.00,
            'status' => 'Draft',
        ]);

        // 13. Seed Invoices
        $inv1 = Invoice::create([
            'id' => (string) Str::ulid(),
            'invoice_number' => 'INV-2026-0001',
            'student_id' => $studentEmma->id,
            'month' => now()->subMonth()->format('Y-m'),
            'total_amount' => 180.00,
            'status' => 'paid',
            'due_date' => now()->subDays(5)->toDateString(),
            'paid_at' => now()->subDays(2)->toDateString(),
        ]);
        InvoiceItem::create([
            'id' => (string) Str::ulid(),
            'invoice_id' => $inv1->id,
            'lesson_id' => $l2->id,
            'description' => 'German Tutoring - 6 Lessons x 1.5h',
            'hours' => 6.0,
            'rate' => 30.0,
            'amount' => 180.00,
        ]);

        $inv2 = Invoice::create([
            'id' => (string) Str::ulid(),
            'invoice_number' => 'INV-2026-0002',
            'student_id' => $studentMax->id,
            'month' => now()->format('Y-m'),
            'total_amount' => 315.00,
            'status' => 'sent',
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
        InvoiceItem::create([
            'id' => (string) Str::ulid(),
            'invoice_id' => $inv2->id,
            'lesson_id' => $l1->id,
            'description' => 'Jobcenter BuT Math Package - 9 Lessons',
            'hours' => 9.0,
            'rate' => 35.0,
            'amount' => 315.00,
        ]);

        // 14. Seed Mobile Quick Login Codes
        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser1->id,
            'code' => 'TEACH123',
            'status' => 'active',
            'created_by' => $adminUser->id,
        ]);

        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $parentUser->id,
            'code' => 'STUD1234',
            'status' => 'active',
            'created_by' => $adminUser->id,
        ]);

        // 15. Seed Chat Channels & Messages
        $channel1 = ChatChannel::create([
            'id' => (string) Str::ulid(),
            'type' => 'group',
            'name' => 'Math Olympiad Prep 2026',
            'created_by' => $adminUser->id,
        ]);
        ChatParticipant::create(['id' => (string) Str::ulid(), 'channel_id' => $channel1->id, 'user_id' => $adminUser->id, 'role' => 'admin']);
        ChatParticipant::create(['id' => (string) Str::ulid(), 'channel_id' => $channel1->id, 'user_id' => $teacherUser1->id, 'role' => 'member']);
        ChatParticipant::create(['id' => (string) Str::ulid(), 'channel_id' => $channel1->id, 'user_id' => $parentUser->id, 'role' => 'member']);

        ChatMessage::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $channel1->id,
            'sender_id' => $adminUser->id,
            'type' => 'text',
            'body' => 'Welcome everyone to the Math Olympiad 2026 preparation group!',
        ]);

        ChatMessage::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $channel1->id,
            'sender_id' => $teacherUser1->id,
            'type' => 'text',
            'body' => 'Hello team! We have prepared advanced algebra and geometry practice sheets for this week.',
        ]);

        // 16. Seed Survey
        $survey = Survey::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $channel1->id,
            'created_by' => $adminUser->id,
            'title' => 'Tutoring Quality Feedback Q3',
            'description' => 'Please rate the quality of your recent tutoring sessions.',
            'status' => 'active',
            'expires_at' => now()->addDays(14),
        ]);

        SurveyQuestion::create([
            'id' => (string) Str::ulid(),
            'survey_id' => $survey->id,
            'question' => 'How would you rate the clarity of explanations in Mathematics?',
            'type' => 'rating',
            'options' => ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'],
            'order' => 1,
        ]);
    }
}

