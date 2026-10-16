<?php

namespace Tests\Feature\Security;

use App\Core\Models\Role;
use App\Core\Models\User;
use App\Core\Models\UserLoginCode;
use App\Core\Models\UserMobileDevice;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel as Subject;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $centerManagerUser;
    private User $teacherUser1;
    private Teacher $teacher1;
    private User $teacherUser2;
    private Teacher $teacher2;
    private User $studentUser1;
    private Student $student1;
    private User $studentUser2;
    private Student $student2;
    private Lesson $lesson1;
    private LessonStudent $lessonStudent1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // 1. Admin & Center Manager
        $this->admin = User::whereHas('roles', fn($q) => $q->where('name', 'Super Admin'))->first();
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $this->centerManagerUser = User::forceCreate([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->centerManagerUser->assignRole('Center Manager');

        \Illuminate\Support\Facades\DB::table('module_settings')->insert([
            'id' => (string) Str::ulid(),
            'module' => 'Nachhilfe',
            'enabled' => true,
        ]);

        // 2. Teacher 1
        $this->teacherUser1 = User::forceCreate([
            'name' => 'Teacher One',
            'email' => 'teacher1@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser1->assignRole('Teacher');
        $this->teacher1 = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->teacherUser1->id,
            'name' => 'Teacher One',
            'email' => 'teacher1@test.com',
            'phone' => '0151111111',
            'hourly_rate' => 25.00,
        ]);

        // 3. Teacher 2
        $this->teacherUser2 = User::forceCreate([
            'name' => 'Teacher Two',
            'email' => 'teacher2@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser2->assignRole('Teacher');
        $this->teacher2 = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->teacherUser2->id,
            'name' => 'Teacher Two',
            'email' => 'teacher2@test.com',
            'phone' => '0152222222',
            'hourly_rate' => 30.00,
        ]);

        // 4. Student 1
        $this->studentUser1 = User::forceCreate([
            'name' => 'Student One',
            'email' => 'student1@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->studentUser1->assignRole('Student');
        $this->student1 = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser1->id,
            'first_name' => 'Student',
            'last_name' => 'One',
            'birth_date' => '2010-01-01',
            'school' => 'Gymnasium',
            'grade' => 8,
            'parent_name' => 'Parent One',
            'parent_phone_1' => '0171111111',
            'parent_email' => 'parent1@test.com',
        ]);

        // 5. Student 2
        $this->studentUser2 = User::forceCreate([
            'name' => 'Student Two',
            'email' => 'student2@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->studentUser2->assignRole('Student');
        $this->student2 = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser2->id,
            'first_name' => 'Student',
            'last_name' => 'Two',
            'birth_date' => '2011-02-02',
            'school' => 'Realschule',
            'grade' => 7,
            'parent_name' => 'Parent Two',
            'parent_phone_1' => '0172222222',
            'parent_email' => 'parent2@test.com',
        ]);

        // 6. Room & Subject
        $room = Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Raum A',
            'capacity' => 5,
        ]);
        $subject = Subject::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mathematik',
        ]);

        // 7. Lesson belonging to Teacher 1 with Student 1
        $this->lesson1 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher1->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'date' => '2026-10-20',
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'scheduled',
            'type' => 'individual',
        ]);

        $this->lessonStudent1 = LessonStudent::create([
            'id' => (string) Str::ulid(),
            'lesson_id' => $this->lesson1->id,
            'student_id' => $this->student1->id,
        ]);
    }

    // ==========================================
    // 1. LOGIN CODE AUTHORIZATION TESTS
    // ==========================================

    public function test_student_cannot_view_another_users_login_code(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/users/{$this->studentUser2->id}/login-code");

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_view_another_teachers_login_code(): void
    {
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/users/{$this->teacherUser2->id}/login-code");

        $response->assertStatus(403);
    }

    public function test_user_can_view_own_login_code(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/users/{$this->studentUser1->id}/login-code");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('login_code.code'));
    }

    public function test_admin_can_view_any_login_code(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/users/{$this->studentUser1->id}/login-code");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('login_code.code'));
    }

    public function test_unauthorized_user_cannot_revoke_another_users_device(): void
    {
        $device = UserMobileDevice::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser2->id,
            'device_name' => 'iPhone 15',
            'device_id' => 'device-xyz',
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->deleteJson("/api/v1/mobile/devices/{$device->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('user_mobile_devices', ['id' => $device->id]);
    }

    // ==========================================
    // 2. ATTENDANCE AUTHORIZATION TESTS
    // ==========================================

    public function test_student_cannot_mark_attendance(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->postJson("/api/v1/nachhilfe/lesson-students/{$this->lessonStudent1->id}/attendance", [
                'status' => 'present',
            ]);

        $response->assertStatus(403);
    }

    public function test_unassigned_teacher_cannot_mark_attendance_for_another_teachers_lesson(): void
    {
        // Teacher 2 tries to mark attendance for Teacher 1's lesson
        $response = $this->actingAs($this->teacherUser2, 'sanctum')
            ->postJson("/api/v1/nachhilfe/lesson-students/{$this->lessonStudent1->id}/attendance", [
                'status' => 'present',
            ]);

        $response->assertStatus(403);
    }

    public function test_assigned_teacher_can_mark_attendance_for_own_lesson(): void
    {
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson("/api/v1/nachhilfe/lesson-students/{$this->lessonStudent1->id}/attendance", [
                'status' => 'present',
                'note' => 'Arrived on time',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'lesson_student_id' => $this->lessonStudent1->id,
            'status' => 'present',
        ]);
    }

    public function test_admin_can_mark_attendance_for_any_lesson(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/nachhilfe/lesson-students/{$this->lessonStudent1->id}/attendance", [
                'status' => 'absent_excused',
                'note' => 'Parent called in',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'lesson_student_id' => $this->lessonStudent1->id,
            'status' => 'absent_excused',
        ]);
    }

    // ==========================================
    // 3. STUDENT PII & DATA AUTHORIZATION TESTS
    // ==========================================

    public function test_student_cannot_list_all_students(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/students');

        $response->assertStatus(403);
    }

    public function test_student_cannot_view_another_students_profile(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student2->id}");

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_view_unassigned_student_profile(): void
    {
        // Teacher 2 does not teach Student 1
        $response = $this->actingAs($this->teacherUser2, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}");

        $response->assertStatus(403);
    }

    public function test_teacher_can_view_assigned_student_profile(): void
    {
        // Teacher 1 teaches Student 1 in Lesson 1
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}");

        $response->assertStatus(200);
        $this->assertEquals($this->student1->id, $response->json('data.id'));
    }

    public function test_student_cannot_delete_any_student(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/students/{$this->student2->id}");

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_delete_any_student(): void
    {
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/students/{$this->student1->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_list_and_view_all_students(): void
    {
        $listResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/nachhilfe/students');
        $listResponse->assertStatus(200);

        $showResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}");
        $showResponse->assertStatus(200);
    }

    // ==========================================
    // 4. TEACHER AUTHORIZATION & DATA PROTECTION
    // ==========================================

    public function test_student_cannot_see_teacher_hourly_rate_and_phone(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$this->teacher1->id}");

        $response->assertStatus(200);
        $this->assertEquals('Teacher One', $response->json('data.name'));
        $this->assertNull($response->json('data.hourly_rate'));
        $this->assertNull($response->json('data.phone'));
    }

    public function test_teacher_cannot_see_another_teachers_hourly_rate(): void
    {
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$this->teacher2->id}");

        $response->assertStatus(200);
        $this->assertEquals('Teacher Two', $response->json('data.name'));
        $this->assertNull($response->json('data.hourly_rate'));
    }

    public function test_teacher_can_see_own_hourly_rate_and_phone(): void
    {
        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$this->teacher1->id}");

        $response->assertStatus(200);
        $this->assertEquals(25.0, (float) $response->json('data.hourly_rate'));
        $this->assertEquals('0151111111', $response->json('data.phone'));
    }

    public function test_admin_and_center_manager_can_see_teacher_hourly_rate_and_phone(): void
    {
        $responseManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$this->teacher1->id}");

        $responseManager->assertStatus(200);
        $this->assertEquals(25.0, (float) $responseManager->json('data.hourly_rate'));
        $this->assertEquals('0151111111', $responseManager->json('data.phone'));

        $responseAdmin = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$this->teacher1->id}");

        $responseAdmin->assertStatus(200);
        $this->assertEquals(25.0, (float) $responseAdmin->json('data.hourly_rate'));
        $this->assertEquals('0151111111', $responseAdmin->json('data.phone'));
    }

    public function test_unauthorized_user_cannot_create_update_or_delete_teacher(): void
    {
        // Student creates teacher -> 403
        $createResponse = $this->actingAs($this->studentUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/teachers', [
                'name' => 'Unauthorized Teacher',
                'qualification' => 'None',
                'hourly_rate' => 20.00,
            ]);
        $createResponse->assertStatus(403);

        // Teacher updates another teacher -> 403
        $updateResponse = $this->actingAs($this->teacherUser1, 'sanctum')
            ->putJson("/api/v1/nachhilfe/teachers/{$this->teacher2->id}", [
                'name' => 'Hacked Teacher',
            ]);
        $updateResponse->assertStatus(403);

        // Teacher deletes another teacher -> 403
        $deleteResponse = $this->actingAs($this->teacherUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/teachers/{$this->teacher2->id}");
        $deleteResponse->assertStatus(403);
    }

    public function test_authorized_user_can_create_update_and_delete_teacher(): void
    {
        $createResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/teachers', [
                'name' => 'New Qualified Teacher',
                'qualification' => 'M.Sc. Physics',
                'hourly_rate' => 28.00,
                'email' => 'physicsteacher@test.com',
            ]);
        $createResponse->assertStatus(201);
        $teacherId = $createResponse->json('data.id');

        $updateResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->putJson("/api/v1/nachhilfe/teachers/{$teacherId}", [
                'name' => 'Senior Physics Teacher',
            ]);
        $updateResponse->assertStatus(200);
        $this->assertEquals('Senior Physics Teacher', $updateResponse->json('data.name'));

        $deleteResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/teachers/{$teacherId}");
        $deleteResponse->assertStatus(204);
    }

    // ==========================================
    // 5. INVOICE AUTHORIZATION TESTS
    // ==========================================

    private function createSampleInvoices(): array
    {
        $inv1 = Invoice::create([
            'id' => (string) Str::ulid(),
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'student_id' => $this->student1->id,
            'month' => '2026-10',
            'total_amount' => 120.00,
            'status' => 'unpaid',
            'due_date' => '2026-10-31',
        ]);

        $inv2 = Invoice::create([
            'id' => (string) Str::ulid(),
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'student_id' => $this->student2->id,
            'month' => '2026-10',
            'total_amount' => 180.00,
            'status' => 'unpaid',
            'due_date' => '2026-10-31',
        ]);

        return [$inv1, $inv2];
    }

    public function test_teacher_cannot_list_all_invoices(): void
    {
        $this->createSampleInvoices();

        $response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/invoices');

        $response->assertStatus(403);
    }

    public function test_student_can_only_list_own_invoices(): void
    {
        [$inv1, $inv2] = $this->createSampleInvoices();

        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/invoices');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($inv1->id, $ids);
        $this->assertNotContains($inv2->id, $ids);
    }

    public function test_student_cannot_view_another_students_invoice(): void
    {
        [$inv1, $inv2] = $this->createSampleInvoices();

        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/invoices/{$inv2->id}");

        $response->assertStatus(403);
    }

    public function test_student_can_view_own_invoice(): void
    {
        [$inv1, $inv2] = $this->createSampleInvoices();

        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/invoices/{$inv1->id}");

        $response->assertStatus(200);
        $this->assertEquals($inv1->id, $response->json('id'));
    }

    public function test_unauthorized_user_cannot_create_update_or_delete_invoice(): void
    {
        [$inv1] = $this->createSampleInvoices();

        // Student creates invoice
        $createRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/invoices', [
                'student_id' => $this->student1->id,
                'month' => '2026-11',
                'total_amount' => 50.00,
            ]);
        $createRes->assertStatus(403);

        // Student updates invoice
        $updateRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->putJson("/api/v1/nachhilfe/invoices/{$inv1->id}", [
                'status' => 'paid',
                'total_amount' => 0.00,
                'due_date' => '2026-10-31',
            ]);
        $updateRes->assertStatus(403);

        // Student updates invoice status
        $statusRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/invoices/{$inv1->id}/status", [
                'status' => 'paid',
            ]);
        $statusRes->assertStatus(403);

        // Student deletes invoice
        $deleteRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/invoices/{$inv1->id}");
        $deleteRes->assertStatus(403);
    }

    public function test_billing_manager_can_manage_invoices(): void
    {
        [$inv1] = $this->createSampleInvoices();

        // Manager lists all invoices
        $listRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/invoices');
        $listRes->assertStatus(200);

        // Manager updates status
        $statusRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/invoices/{$inv1->id}/status", [
                'status' => 'paid',
            ]);
        $statusRes->assertStatus(200);
        $this->assertEquals('paid', $statusRes->json('invoice.status'));

        // Manager deletes invoice
        $delRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/invoices/{$inv1->id}");
        $delRes->assertStatus(200);
    }

    // ==========================================
    // 6. PAYROLL AUTHORIZATION TESTS
    // ==========================================

    private function createSamplePayrolls(): array
    {
        $p1 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher1->id,
            'month' => '2026-10',
            'total_completed_lessons' => 2,
            'total_hours' => 3.0,
            'total_amount' => 75.0,
            'status' => 'Draft',
        ]);

        $p2 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher2->id,
            'month' => '2026-10',
            'total_completed_lessons' => 4,
            'total_hours' => 6.0,
            'total_amount' => 180.0,
            'status' => 'Draft',
        ]);

        return [$p1, $p2];
    }

    public function test_unauthorized_user_cannot_view_payroll_summary_or_approve(): void
    {
        // Teacher tries summary
        $sumRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/payrolls/summary?month=2026-10');
        $sumRes->assertStatus(403);

        // Teacher tries approve
        $appRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls/approve', ['month' => '2026-10']);
        $appRes->assertStatus(403);

        // Student tries summary
        $stuRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/payrolls/summary?month=2026-10');
        $stuRes->assertStatus(403);
    }

    public function test_billing_manager_can_view_payroll_summary_and_approve(): void
    {
        // Ensure a completed lesson exists for the month
        $this->lesson1->update([
            'status' => 'completed',
            'date' => '2026-10-10',
        ]);

        $sumRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/payrolls/summary?month=2026-10');
        $sumRes->assertStatus(200);

        $appRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls/approve', ['month' => '2026-10']);
        $appRes->assertStatus(200);
        $this->assertEquals('Payroll snapshot approved successfully', $appRes->json('message'));
    }

    public function test_teacher_can_only_view_own_payrolls(): void
    {
        [$p1, $p2] = $this->createSamplePayrolls();

        // Listing payrolls as Teacher 1 returns only Payroll 1
        $listRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/payrolls');
        $listRes->assertStatus(200);
        $ids = collect($listRes->json('data'))->pluck('id')->all();
        $this->assertContains($p1->id, $ids);
        $this->assertNotContains($p2->id, $ids);

        // Teacher 1 views own payroll -> 200
        $showOwnRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/payrolls/{$p1->id}");
        $showOwnRes->assertStatus(200);

        // Teacher 1 views Teacher 2's payroll -> 403
        $showOtherRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/payrolls/{$p2->id}");
        $showOtherRes->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_modify_or_delete_payrolls(): void
    {
        [$p1] = $this->createSamplePayrolls();

        // Student cannot list payrolls
        $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/payrolls')
            ->assertStatus(403);

        // Teacher cannot modify payroll total
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->putJson("/api/v1/nachhilfe/payrolls/{$p1->id}", [
                'status' => 'Paid',
                'total_amount' => 9999.00,
            ])
            ->assertStatus(403);

        // Teacher cannot delete payroll
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$p1->id}")
            ->assertStatus(403);
    }

    // ==========================================
    // 7. USER & ROLE PRIVILEGE ESCALATION TESTS
    // ==========================================

    public function test_unauthorized_user_cannot_list_or_create_users(): void
    {
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(403);

        $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(403);

        $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Injected User',
                'email' => 'injected@test.com',
                'password' => 'password123',
            ])
            ->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_list_or_modify_roles_or_permissions(): void
    {
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/roles')
            ->assertStatus(403);

        $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/roles')
            ->assertStatus(403);

        $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/permissions')
            ->assertStatus(403);

        $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/roles', [
                'name' => 'Injected Role',
            ])
            ->assertStatus(403);
    }

    public function test_non_super_admin_cannot_escalate_to_super_admin(): void
    {
        $userManager = User::forceCreate([
            'name' => 'User Admin Non Super',
            'email' => 'usermanager@test.com',
            'password' => bcrypt('password123'),
        ]);
        $userManager->givePermissionTo('manage users');

        // Cannot create user with Super Admin role
        $createRes = $this->actingAs($userManager, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Escalated Admin',
                'email' => 'escalated@test.com',
                'password' => 'password123',
                'roles' => ['Super Admin'],
            ]);
        $createRes->assertStatus(403);

        // Can create user with Teacher role
        $createOk = $this->actingAs($userManager, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Regular Teacher',
                'email' => 'regteacher@test.com',
                'password' => 'password123',
                'roles' => ['Teacher'],
            ]);
        $createOk->assertStatus(201);
    }

    public function test_super_admin_can_manage_users_and_roles(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/roles')
            ->assertStatus(200);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/permissions')
            ->assertStatus(200);
    }

    public function test_unauthenticated_access_to_public_settings_does_not_disclose_secrets(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['id' => (string) Str::ulid(), 'key' => 'twilio_sid', 'value' => 'AC_SUPER_SECRET', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::ulid(), 'key' => 'twilio_token', 'value' => 'SECRET_TOKEN_XYZ', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::ulid(), 'key' => 'center_name', 'value' => 'My Public Academy', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \Illuminate\Support\Facades\Cache::forget(\App\Core\Services\CenterSettingsService::CACHE_KEY);

        $response = $this->getJson('/api/v1/settings');
        $response->assertStatus(200);

        // Public branding is available
        $this->assertEquals('My Public Academy', $response->json('center.name'));
        $this->assertFalse($response->json('permissions.can_manage'));

        // Secrets are not exposed in raw_settings for public visitors
        $raw = $response->json('raw_settings');
        $this->assertArrayNotHasKey('twilio_sid', $raw);
        $this->assertArrayNotHasKey('twilio_token', $raw);
        $this->assertArrayHasKey('center_name', $raw);
    }

    public function test_authorized_manager_can_access_private_settings_and_update(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['id' => (string) Str::ulid(), 'key' => 'twilio_sid', 'value' => 'AC_AUTHORIZED_KEY', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \Illuminate\Support\Facades\Cache::forget(\App\Core\Services\CenterSettingsService::CACHE_KEY);

        // 1. Authorized manager can read private settings
        $response = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson('/api/v1/settings');
        $response->assertStatus(200);
        $this->assertTrue($response->json('permissions.can_manage'));
        $this->assertEquals('AC_AUTHORIZED_KEY', $response->json('raw_settings.twilio_sid'));

        // 2. Authorized manager can update settings
        $updateRes = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'center_name', 'value' => 'Updated Academy Name'],
                ]
            ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'key' => 'center_name',
            'value' => 'Updated Academy Name',
        ]);

        // 3. Unauthorized user cannot update settings
        $forbiddenRes = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'center_name', 'value' => 'Hacked Name'],
                ]
            ]);
        $forbiddenRes->assertStatus(403);
    }

    public function test_non_super_admin_cannot_modify_or_delete_super_admin_account(): void
    {
        $userManager = User::forceCreate([
            'name' => 'Delegated User Manager',
            'email' => 'delegated_user_mgr@test.com',
            'password' => bcrypt('password123'),
        ]);
        $userManager->givePermissionTo('manage users');

        // Attempt to update Super Admin's password/name
        $updateRes = $this->actingAs($userManager, 'sanctum')
            ->putJson("/api/v1/users/{$this->admin->id}", [
                'name' => 'Compromised Admin',
                'password' => 'new_hacked_password',
            ]);
        $updateRes->assertStatus(403);

        // Attempt to delete Super Admin
        $deleteRes = $this->actingAs($userManager, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->admin->id}");
        $deleteRes->assertStatus(403);

        // Super Admin can update themselves or other accounts
        $adminSelfUpdate = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/users/{$this->admin->id}", [
                'name' => 'Super Admin Updated',
            ]);
        $adminSelfUpdate->assertStatus(200);
    }

    public function test_system_logs_and_audit_logs_require_proper_authorization(): void
    {
        // 1. Unauthenticated requests are rejected with 401
        $this->getJson('/api/v1/audit-logs')->assertStatus(401);
        $this->getJson('/api/v1/system-logs')->assertStatus(401);
        $this->getJson('/api/v1/system-logs/export')->assertStatus(401);

        // 2. Unauthorized roles (Teacher, Student) are rejected with 403
        $this->actingAs($this->teacherUser1, 'sanctum')->getJson('/api/v1/audit-logs')->assertStatus(403);
        $this->actingAs($this->studentUser1, 'sanctum')->getJson('/api/v1/audit-logs')->assertStatus(403);
        $this->actingAs($this->teacherUser1, 'sanctum')->getJson('/api/v1/system-logs')->assertStatus(403);
        $this->actingAs($this->teacherUser1, 'sanctum')->getJson('/api/v1/system-logs/export')->assertStatus(403);

        // 3. Authorized Super Admin can access both
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/audit-logs')->assertStatus(200);
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/system-logs')->assertStatus(200);
    }

    public function test_dashboard_and_calendar_responses_redact_sensitive_teacher_and_student_pii(): void
    {
        // 1. Dashboard stats authorization
        $this->getJson('/api/v1/nachhilfe/dashboard/stats')->assertStatus(401);
        $this->actingAs($this->studentUser1, 'sanctum')->getJson('/api/v1/nachhilfe/dashboard/stats')->assertStatus(403);

        // Teacher can view dashboard, but sensitive fields must be redacted
        $teacherDashboard = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/dashboard/stats');
        $teacherDashboard->assertStatus(200);

        // Live status teachers: only id, name, is_teaching
        $teachersLive = $teacherDashboard->json('data.live_status.teachers');
        $this->assertNotEmpty($teachersLive);
        $this->assertArrayNotHasKey('hourly_rate', $teachersLive[0]);
        $this->assertArrayNotHasKey('phone', $teachersLive[0]);
        $this->assertArrayHasKey('id', $teachersLive[0]);
        $this->assertArrayHasKey('name', $teachersLive[0]);

        // Financial stats should be 0 or empty for non-billing roles
        $this->assertEquals(0.0, $teacherDashboard->json('data.stats.revenue_this_month'));
        $this->assertEmpty($teacherDashboard->json('data.recent_invoices'));

        // Center Manager can view full financial metrics
        $managerDashboard = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/dashboard/stats');
        $managerDashboard->assertStatus(200);

        // 2. Calendar feed PII redaction for students
        $room = Room::create(['id' => (string) Str::ulid(), 'name' => 'Room Alpha', 'capacity' => 10]);
        $subject = Subject::create(['id' => (string) Str::ulid(), 'name' => 'Math', 'is_active' => true]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher1->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $lesson->students()->attach($this->student1->id, [
            'id' => (string) Str::ulid(),
            'hours_consumed' => 0,
        ]);

        $calendarRes = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/calendar');
        $calendarRes->assertStatus(200);

        $lessons = $calendarRes->json('lessons');
        $this->assertNotEmpty($lessons);
        $firstLesson = $lessons[0];

        // Teacher hourly rate must be hidden from student
        $this->assertArrayNotHasKey('hourly_rate', $firstLesson['teacher'] ?? []);

        // Student parent contact details must be hidden
        $lessonStudents = $firstLesson['students'] ?? [];
        $this->assertNotEmpty($lessonStudents);
        $this->assertArrayNotHasKey('parent_phone_1', $lessonStudents[0]);
        $this->assertArrayNotHasKey('parent_email', $lessonStudents[0]);
    }

    public function test_package_room_subject_and_lesson_mutation_authorization(): void
    {
        // 1. Package mutations
        $packageStoreByTeacher = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/packages', [
                'name' => 'Gold 20 Hours',
                'hours' => 20,
                'price' => 300,
            ]);
        $packageStoreByTeacher->assertStatus(403);

        $packageStoreByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/packages', [
                'name' => 'Gold 20 Hours',
                'hours' => 20,
                'price' => 300,
            ]);
        $packageStoreByManager->assertStatus(201);
        $packageId = $packageStoreByManager->json('id');

        $this->actingAs($this->teacherUser1, 'sanctum')
            ->putJson("/api/v1/nachhilfe/packages/{$packageId}", ['name' => 'Changed'])
            ->assertStatus(403);

        $this->actingAs($this->teacherUser1, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/packages/{$packageId}")
            ->assertStatus(403);

        // 2. Room mutations
        $roomStoreByTeacher = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/rooms', [
                'name' => 'Room Beta',
                'capacity' => 8,
            ]);
        $roomStoreByTeacher->assertStatus(403);

        $roomStoreByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/rooms', [
                'name' => 'Room Beta',
                'capacity' => 8,
            ]);
        $roomStoreByManager->assertStatus(201);
        $roomId = $roomStoreByManager->json('data.id');

        // 3. Subject mutations
        $subjectStoreByTeacher = $this->actingAs($this->teacherUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/subjects', [
                'name' => 'Physics',
            ]);
        $subjectStoreByTeacher->assertStatus(403);

        $subjectStoreByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/subjects', [
                'name' => 'Physics',
            ]);
        $subjectStoreByManager->assertStatus(201);
        $subjectId = $subjectStoreByManager->json('data.id');

        // 4. Lesson booking and status mutations
        $bookDate = now()->addDays(10)->toDateString();
        $dayOfWeek = \Carbon\Carbon::parse($bookDate)->dayOfWeek;
        TeacherAvailability::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);

        $lessonBookByStudent = $this->actingAs($this->studentUser1, 'sanctum')
            ->postJson('/api/v1/nachhilfe/lessons', [
                'teacher_id' => $this->teacher1->id,
                'room_id' => $roomId,
                'subject_id' => $subjectId,
                'type' => 'individual',
                'date' => $bookDate,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'students' => [
                    ['student_id' => $this->student1->id],
                ],
            ]);
        $lessonBookByStudent->assertStatus(403);

        $lessonBookByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/nachhilfe/lessons', [
                'teacher_id' => $this->teacher1->id,
                'room_id' => $roomId,
                'subject_id' => $subjectId,
                'type' => 'individual',
                'date' => $bookDate,
                'start_time' => '09:00',
                'end_time' => '10:00',
                'students' => [
                    ['student_id' => $this->student1->id],
                ],
            ]);
        $lessonBookByManager->assertStatus(201);
        $lessonId = $lessonBookByManager->json('data.id');

        // Student cannot update lesson status
        $this->actingAs($this->studentUser1, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lessonId}/status", [
                'status' => 'rescheduled',
            ])
            ->assertStatus(403);

        // Unassigned teacher (teacher2) cannot update lesson status
        $this->actingAs($this->teacherUser2, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lessonId}/status", [
                'status' => 'rescheduled',
            ])
            ->assertStatus(403);

        // Assigned teacher (teacher1) CAN update lesson status
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lessonId}/status", [
                'status' => 'rescheduled',
            ])
            ->assertStatus(200);
    }

    public function test_non_super_admin_cannot_view_or_regenerate_super_admin_login_code(): void
    {
        // 1. Center Manager cannot view Super Admin login code
        $viewByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson("/api/v1/users/{$this->admin->id}/login-code");
        $viewByManager->assertStatus(403);

        // 2. Center Manager cannot regenerate Super Admin login code
        $regenByManager = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson("/api/v1/users/{$this->admin->id}/login-code/regenerate", [
                'admin_password' => 'password123',
            ]);
        $regenByManager->assertStatus(403);

        // 3. Super Admin CAN view their own login code
        $viewBySelf = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/users/{$this->admin->id}/login-code");
        $viewBySelf->assertStatus(200);
        $code = $viewBySelf->json('login_code.code');
        $this->assertNotEmpty($code);

        // 4. Mobile code login exchange rejects non-Student/Teacher (Super Admin)
        $exchangeResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $code,
            'device_name' => 'Pixel 9 Pro',
            'device_id' => 'device-admin-test',
        ]);
        $exchangeResponse->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_student_mobile_lessons_does_not_expose_teacher_hourly_rate_or_phone(): void
    {
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/mobile/student/lessons');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Ensure teacher display fields are present while wage and phone are absent
        $this->assertEquals($this->teacher1->name, $data[0]['teacher']['name']);
        $this->assertArrayNotHasKey('hourly_rate', $data[0]['teacher']);
        $this->assertArrayNotHasKey('phone', $data[0]['teacher']);
    }

    public function test_student_timeline_object_level_authorization(): void
    {
        // 1. Student 1 cannot view Student 2's timeline
        $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student2->id}/timeline")
            ->assertStatus(403);

        // 2. Teacher 2 (who does not teach Student 1) cannot view Student 1's timeline
        $this->actingAs($this->teacherUser2, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}/timeline")
            ->assertStatus(403);

        // 3. Student 1 can view own timeline
        $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}/timeline")
            ->assertStatus(200);

        // 4. Teacher 1 (who teaches Student 1) can view Student 1's timeline
        $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}/timeline")
            ->assertStatus(200);

        // 5. Center Manager can view Student 1's timeline
        $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$this->student1->id}/timeline")
            ->assertStatus(200);
    }

    public function test_lesson_index_object_level_authorization_and_nested_student_isolation(): void
    {
        // Add Student 2 to lesson 1 to create a group lesson
        LessonStudent::create([
            'id' => (string) Str::ulid(),
            'lesson_id' => $this->lesson1->id,
            'student_id' => $this->student2->id,
        ]);

        // 1. Student 1 queries lesson index: only sees their own student record inside the lesson
        $studentResponse = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/lessons');
        $studentResponse->assertStatus(200);
        $studentLessons = $studentResponse->json('data');
        $this->assertNotEmpty($studentLessons);
        $this->assertCount(1, $studentLessons[0]['students']);
        $this->assertEquals($this->student1->id, $studentLessons[0]['students'][0]['id']);

        // 2. Teacher 2 (not assigned) queries lessons: receives empty list
        $teacher2Response = $this->actingAs($this->teacherUser2, 'sanctum')
            ->getJson('/api/v1/nachhilfe/lessons');
        $teacher2Response->assertStatus(200);
        $this->assertEmpty($teacher2Response->json('data'));

        // 3. Teacher 1 (assigned) queries lessons: receives lesson with both students
        $teacher1Response = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson('/api/v1/nachhilfe/lessons');
        $teacher1Response->assertStatus(200);
        $this->assertNotEmpty($teacher1Response->json('data'));
        $this->assertCount(2, $teacher1Response->json('data.0.students'));

        // 4. Center Manager receives center-wide lessons and all student records
        $managerResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/lessons');
        $managerResponse->assertStatus(200);
        $this->assertNotEmpty($managerResponse->json('data'));
    }

    public function test_audit_log_redacts_sensitive_credentials_in_database_and_api(): void
    {
        $secretToken = 'super_secret_twilio_auth_token_999';
        $secretPassword = 'super_secret_smtp_password_888';

        $updateResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'twilio_token', 'value' => $secretToken],
                    ['key' => 'smtp_password', 'value' => $secretPassword],
                ],
            ]);
        $updateResponse->assertStatus(200);

        // Verify database audit_logs table contains [REDACTED] and does NOT contain raw secret
        $auditLog = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event', 'center_settings_updated')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringNotContainsString($secretToken, $auditLog->new_values);
        $this->assertStringNotContainsString($secretPassword, $auditLog->new_values);
        $this->assertStringContainsString('[REDACTED]', $auditLog->new_values);

        // Verify API response redacts sensitive keys
        $apiResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/audit-logs');
        $apiResponse->assertStatus(200);
        $logs = $apiResponse->json('data');
        $this->assertNotEmpty($logs);
        $latestLog = collect($logs)->firstWhere('event', 'center_settings_updated');
        $this->assertNotNull($latestLog);
        $this->assertEquals('[REDACTED]', $latestLog['new_values']['twilio_token'] ?? null);
        $this->assertEquals('[REDACTED]', $latestLog['new_values']['smtp_password'] ?? null);
    }

    public function test_canonical_manage_settings_permission_can_delete_center_logo(): void
    {
        // 1. Create a user with canonical 'manage settings' permission (without Super Admin role)
        $settingsOfficer = User::forceCreate([
            'name' => 'Settings Officer',
            'email' => 'officer@test.com',
            'password' => bcrypt('password123'),
        ]);
        $settingsOfficer->givePermissionTo('manage settings');

        // Can delete logo
        $response = $this->actingAs($settingsOfficer, 'sanctum')
            ->deleteJson('/api/v1/settings/logo');
        $response->assertStatus(200);

        // 2. Unauthorized user cannot delete logo
        $unauthorized = $this->actingAs($this->studentUser1, 'sanctum')
            ->deleteJson('/api/v1/settings/logo');
        $unauthorized->assertStatus(403);
    }

    /**
     * =========================================================================
     * PHASE 5 SECURITY REGRESSION TESTS
     * =========================================================================
     */

    public function test_student_mobile_lessons_does_not_leak_peer_students_in_group_lesson(): void
    {
        // 1. Ensure Lesson 1 has both Student 1 and Student 2 enrolled
        LessonStudent::firstOrCreate([
            'lesson_id' => $this->lesson1->id,
            'student_id' => $this->student2->id,
        ], [
            'id' => (string) Str::ulid(),
            'hours_consumed' => 2.0,
            'notes' => 'Confidential note about Student 2',
        ]);

        // 2. Student 1 calls mobile lessons endpoint
        $response = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson('/api/v1/mobile/student/lessons');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Find the group lesson in response
        $matchedLesson = collect($data)->firstWhere('id', $this->lesson1->id);
        $this->assertNotNull($matchedLesson);

        // Verify lesson_students contains Student 1's record but ZERO peer records
        $this->assertArrayHasKey('lesson_students', $matchedLesson);
        $peerStudentIds = collect($matchedLesson['lesson_students'])->pluck('student_id');
        $this->assertTrue($peerStudentIds->contains($this->student1->id));
        $this->assertFalse($peerStudentIds->contains($this->student2->id));

        // Ensure notes or private fields of Student 2 are completely absent
        $serializedJson = json_encode($matchedLesson['lesson_students']);
        $this->assertStringNotContainsString('Confidential note about Student 2', $serializedJson);
        $this->assertStringNotContainsString($this->student2->id, $serializedJson);
    }

    public function test_calendar_denies_authenticated_users_without_management_or_domain_authorization(): void
    {
        // User with no roles
        $unassignedUser = User::forceCreate([
            'name' => 'Unassigned User',
            'email' => 'unassigned@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($unassignedUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/calendar');

        $response->assertStatus(403);
    }

    public function test_calendar_denies_teacher_or_student_without_domain_profile(): void
    {
        // 1. User with Teacher role but no Teacher model
        $orphanTeacher = User::forceCreate([
            'name' => 'Orphan Teacher',
            'email' => 'orphan_teacher@test.com',
            'password' => bcrypt('password123'),
        ]);
        $orphanTeacher->assignRole('Teacher');

        $this->actingAs($orphanTeacher, 'sanctum')
            ->getJson('/api/v1/nachhilfe/calendar')
            ->assertStatus(403);

        // 2. User with Student role but no Student model
        $orphanStudent = User::forceCreate([
            'name' => 'Orphan Student',
            'email' => 'orphan_student@test.com',
            'password' => bcrypt('password123'),
        ]);
        $orphanStudent->assignRole('Student');

        $this->actingAs($orphanStudent, 'sanctum')
            ->getJson('/api/v1/nachhilfe/calendar')
            ->assertStatus(403);
    }

    public function test_calendar_scoping_for_authorized_teacher_student_and_management(): void
    {
        $date = $this->lesson1->date;

        // 1. Authorized Teacher 1 sees their assigned lesson
        $teacherResponse = $this->actingAs($this->teacherUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $teacherResponse->assertStatus(200);
        $teacherLessons = $teacherResponse->json('lessons');
        $this->assertNotEmpty($teacherLessons);
        foreach ($teacherLessons as $lesson) {
            $this->assertEquals($this->teacher1->id, $lesson['teacher_id']);
        }

        // Unassigned Teacher 2 sees no lessons for this slot
        $teacher2Response = $this->actingAs($this->teacherUser2, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $teacher2Response->assertStatus(200);
        $this->assertEmpty($teacher2Response->json('lessons'));

        // 2. Authorized Student 1 sees their enrolled lesson
        $studentResponse = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $studentResponse->assertStatus(200);
        $studentLessons = $studentResponse->json('lessons');
        $this->assertNotEmpty($studentLessons);
        foreach ($studentLessons as $lesson) {
            $studentIds = collect($lesson['students'])->pluck('id');
            $this->assertTrue($studentIds->contains($this->student1->id));
        }

        // Unenrolled Student 2 sees no lessons for this slot
        $student2Response = $this->actingAs($this->studentUser2, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $student2Response->assertStatus(200);
        $this->assertEmpty($student2Response->json('lessons'));

        // 3. Authorized Manager sees center-wide calendar
        $managerResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $managerResponse->assertStatus(200);
        $this->assertNotEmpty($managerResponse->json('lessons'));
    }

    public function test_login_code_expires_and_cannot_authenticate_after_expiration(): void
    {
        $expiredCode = 'EXPIR123';
        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser1->id,
            'code' => $expiredCode,
            'status' => 'active',
            'expires_at' => now()->subMinutes(10), // expired 10 minutes ago
        ]);

        $response = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $expiredCode,
            'device_name' => 'Expired Test Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_login_code_single_use_cannot_be_reused_after_login(): void
    {
        $singleUseCode = 'ONETM123';
        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser1->id,
            'code' => $singleUseCode,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        // First login: succeeds
        $firstResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $singleUseCode,
            'device_name' => 'First Device',
        ]);
        $firstResponse->assertStatus(200);
        $this->assertNotEmpty($firstResponse->json('access_token'));

        // Database status must be 'used'
        $dbCode = UserLoginCode::where('code', $singleUseCode)->first();
        $this->assertEquals('used', $dbCode->status);

        // Second login (replay attack / reuse): MUST fail
        $secondResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $singleUseCode,
            'device_name' => 'Second Device',
        ]);
        $secondResponse->assertStatus(422);
        $secondResponse->assertJsonValidationErrors(['code']);
    }

    public function test_administrative_and_non_student_teacher_accounts_cannot_authenticate_via_mobile_code(): void
    {
        // 1. Super Admin code exchange MUST fail
        $superAdminCode = 'ADMIN123';
        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->admin->id,
            'code' => $superAdminCode,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        $adminResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $superAdminCode,
            'device_name' => 'Admin Mobile',
        ]);
        $adminResponse->assertStatus(422);
        $adminResponse->assertJsonValidationErrors(['code']);

        // 2. Center Manager code exchange MUST fail
        $managerCode = 'MNGR1234';
        UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->centerManagerUser->id,
            'code' => $managerCode,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        $managerResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $managerCode,
            'device_name' => 'Manager Mobile',
        ]);
        $managerResponse->assertStatus(422);
        $managerResponse->assertJsonValidationErrors(['code']);
    }

    public function test_mobile_auth_code_endpoint_is_rate_limited(): void
    {
        // 5 requests succeed or fail with 422
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/mobile/auth/code', [
                'code' => 'WRONG' . $i,
                'device_name' => 'Spam Device',
            ]);
        }

        // 6th request MUST be throttled with 429
        $throttledResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'WRONG99',
            'device_name' => 'Spam Device',
        ]);

        $throttledResponse->assertStatus(429);
    }

    public function test_recursive_audit_log_redaction_and_false_positive_immunity(): void
    {
        $dirtyPayload = [
            'integrations' => [
                'smtp' => [
                    'password' => 'TOP_SECRET_PASSWORD',
                    'host' => 'smtp.example.com',
                ],
                'twilio' => [
                    'twilio_auth_token' => 'SECRET_TWILIO_TOKEN',
                ],
            ],
            'device' => [
                'token_id' => '01JABCDEF1234567890',
                'device_id' => 'DEV_98765',
                'keyboard' => 'Mechanical Keyboard',
                'secretary' => 'Jane Administrative Assistant',
            ],
        ];

        $redacted = \App\Core\Support\AuditSensitiveDataRedactor::redact($dirtyPayload);

        // 1. Secrets MUST be redacted
        $this->assertEquals('[REDACTED]', $redacted['integrations']['smtp']['password']);
        $this->assertEquals('[REDACTED]', $redacted['integrations']['twilio']['twilio_auth_token']);

        // 2. Non-secrets and innocent identifiers MUST remain untouched (Zero false-positives)
        $this->assertEquals('smtp.example.com', $redacted['integrations']['smtp']['host']);
        $this->assertEquals('01JABCDEF1234567890', $redacted['device']['token_id']);
        $this->assertEquals('DEV_98765', $redacted['device']['device_id']);
        $this->assertEquals('Mechanical Keyboard', $redacted['device']['keyboard']);
        $this->assertEquals('Jane Administrative Assistant', $redacted['device']['secretary']);
    }

    public function test_historical_audit_log_cleanup_migration_is_idempotent(): void
    {
        $auditId = (string) Str::ulid();
        $initialOld = json_encode([
            'smtp' => ['password' => 'RAW_OLD_PASSWORD'],
            'device' => ['token_id' => '01JOLDTOKEN123', 'keyboard' => 'Wireless'],
        ]);
        $initialNew = json_encode([
            'smtp' => ['password' => 'RAW_NEW_PASSWORD'],
            'device' => ['token_id' => '01JNEWTOKEN456', 'secretary' => 'Support Desk'],
        ]);

        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'id' => $auditId,
            'user_id' => $this->admin->id,
            'event' => 'custom_historical_event',
            'auditable_type' => 'Setting',
            'auditable_id' => (string) Str::ulid(),
            'old_values' => $initialOld,
            'new_values' => $initialNew,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = new class extends \Illuminate\Database\Migrations\Migration {
            public function runMigration(): void
            {
                \Illuminate\Support\Facades\DB::table('audit_logs')
                    ->orderBy('id')
                    ->chunk(200, function ($records) {
                        foreach ($records as $record) {
                            $needsUpdate = false;
                            $updates = [];

                            if (!empty($record->old_values) && is_string($record->old_values)) {
                                $decoded = json_decode($record->old_values, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $redacted = \App\Core\Support\AuditSensitiveDataRedactor::redact($decoded);
                                    $encoded = json_encode($redacted);
                                    if ($encoded !== $record->old_values) {
                                        $updates['old_values'] = $encoded;
                                        $needsUpdate = true;
                                    }
                                }
                            }

                            if (!empty($record->new_values) && is_string($record->new_values)) {
                                $decoded = json_decode($record->new_values, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $redacted = \App\Core\Support\AuditSensitiveDataRedactor::redact($decoded);
                                    $encoded = json_encode($redacted);
                                    if ($encoded !== $record->new_values) {
                                        $updates['new_values'] = $encoded;
                                        $needsUpdate = true;
                                    }
                                }
                            }

                            if ($needsUpdate) {
                                \Illuminate\Support\Facades\DB::table('audit_logs')->where('id', $record->id)->update($updates);
                            }
                        }
                    });
            }
        };

        // 1. Run migration first time
        $migration->runMigration();

        $recordAfterFirstRun = \Illuminate\Support\Facades\DB::table('audit_logs')->where('id', $auditId)->first();
        $decodedOld = json_decode($recordAfterFirstRun->old_values, true);
        $decodedNew = json_decode($recordAfterFirstRun->new_values, true);

        // Passwords redacted
        $this->assertEquals('[REDACTED]', $decodedOld['smtp']['password']);
        $this->assertEquals('[REDACTED]', $decodedNew['smtp']['password']);

        // Identifiers intact
        $this->assertEquals('01JOLDTOKEN123', $decodedOld['device']['token_id']);
        $this->assertEquals('Wireless', $decodedOld['device']['keyboard']);
        $this->assertEquals('01JNEWTOKEN456', $decodedNew['device']['token_id']);
        $this->assertEquals('Support Desk', $decodedNew['device']['secretary']);

        // 2. Run migration SECOND time (Idempotency check)
        $migration->runMigration();

        $recordAfterSecondRun = \Illuminate\Support\Facades\DB::table('audit_logs')->where('id', $auditId)->first();
        $this->assertEquals($recordAfterFirstRun->old_values, $recordAfterSecondRun->old_values);
        $this->assertEquals($recordAfterFirstRun->new_values, $recordAfterSecondRun->new_values);
    }

    public function test_calendar_nested_lesson_students_isolation_for_students_and_management(): void
    {
        $date = $this->lesson1->date;

        // Ensure Lesson 1 has both Student 1 and Student 2 enrolled with confidential notes
        LessonStudent::firstOrCreate([
            'lesson_id' => $this->lesson1->id,
            'student_id' => $this->student2->id,
        ], [
            'id' => (string) Str::ulid(),
            'hours_consumed' => 2.0,
            'notes' => 'Confidential note for Student 2 in Calendar',
        ]);

        // 1. Student 1 requests calendar
        $studentResponse = $this->actingAs($this->studentUser1, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $studentResponse->assertStatus(200);

        $matchedLesson = collect($studentResponse->json('lessons'))->firstWhere('id', $this->lesson1->id);
        $this->assertNotNull($matchedLesson);

        // Student 1's record is present
        $peerStudentIds = collect($matchedLesson['lesson_students'])->pluck('student_id');
        $this->assertTrue($peerStudentIds->contains($this->student1->id));

        // Student 2's record is strictly absent
        $this->assertFalse($peerStudentIds->contains($this->student2->id));

        // Confidential notes or student 2 identifiers are not leaked
        $serializedJson = json_encode($matchedLesson['lesson_students']);
        $this->assertStringNotContainsString('Confidential note for Student 2 in Calendar', $serializedJson);
        $this->assertStringNotContainsString($this->student2->id, $serializedJson);

        // 2. Center Manager requests calendar: retains full visibility of all enrolled students
        $managerResponse = $this->actingAs($this->centerManagerUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");
        $managerResponse->assertStatus(200);

        $managerLesson = collect($managerResponse->json('lessons'))->firstWhere('id', $this->lesson1->id);
        $this->assertNotNull($managerLesson);
        $managerStudentIds = collect($managerLesson['lesson_students'])->pluck('student_id');
        $this->assertTrue($managerStudentIds->contains($this->student1->id));
        $this->assertTrue($managerStudentIds->contains($this->student2->id));
    }

    public function test_timeline_read_time_audit_redaction_prevents_credential_leak(): void
    {
        $dirtyOld = json_encode([
            'smtp' => ['password' => 'TOP_SECRET_OLD_PASS'],
            'device' => ['token_id' => '01JTIMELINEOLD', 'keyboard' => 'Mechanical'],
        ]);
        $dirtyNew = json_encode([
            'auth' => ['access_token' => 'RAW_ACCESS_TOKEN_SECRET'],
            'device' => ['token_id' => '01JTIMELINENEW', 'secretary' => 'Admin Desk'],
        ]);

        $auditId = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'id' => $auditId,
            'user_id' => $this->admin->id,
            'event' => 'updated',
            'auditable_type' => \App\Modules\Nachhilfe\Infrastructure\Models\Student::class,
            'auditable_id' => $this->student1->id,
            'old_values' => $dirtyOld,
            'new_values' => $dirtyNew,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Direct AuditTimelineProvider testing
        $provider = app(\App\Modules\Nachhilfe\Infrastructure\Timeline\AuditTimelineProvider::class);
        $events = $provider->getEvents($this->student1);
        $this->assertNotEmpty($events);

        $matchedEvent = collect($events)->first(fn($e) => ($e['metadata']['audit_id'] ?? null) === $auditId);
        $this->assertNotNull($matchedEvent);

        $metadata = $matchedEvent['metadata'];
        // Secrets MUST be redacted
        $this->assertEquals('[REDACTED]', $metadata['old_values']['smtp']['password']);
        $this->assertEquals('[REDACTED]', $metadata['new_values']['auth']['access_token']);

        // Innocent identifiers MUST remain intact
        $this->assertEquals('01JTIMELINEOLD', $metadata['old_values']['device']['token_id']);
        $this->assertEquals('Mechanical', $metadata['old_values']['device']['keyboard']);
        $this->assertEquals('01JTIMELINENEW', $metadata['new_values']['device']['token_id']);
        $this->assertEquals('Admin Desk', $metadata['new_values']['device']['secretary']);

        // 2. Student timeline builder testing (Admin receives change events with redaction)
        $adminEvents = app(\App\Modules\Nachhilfe\Application\Services\StudentTimelineBuilder::class)->build($this->student1, $this->admin);
        $adminChangeEvent = collect($adminEvents)->first(fn($e) => ($e['metadata']['audit_id'] ?? null) === $auditId);
        $this->assertNotNull($adminChangeEvent);
        $this->assertEquals('[REDACTED]', $adminChangeEvent['metadata']['old_values']['smtp']['password']);
        $this->assertEquals('[REDACTED]', $adminChangeEvent['metadata']['new_values']['auth']['access_token']);
    }

    public function test_legacy_null_login_code_migration_and_security_lifecycle(): void
    {
        // Test A: Legacy active code created 25 hours ago with expires_at = NULL
        $codeA = 'LEGACY25';
        $idA = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('user_login_codes')->insert([
            'id' => $idA,
            'user_id' => $this->studentUser1->id,
            'code' => $codeA,
            'status' => 'active',
            'expires_at' => null,
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);

        // Test B: Recent legacy code created 2 hours ago with expires_at = NULL
        $codeB = 'LEGACY02';
        $idB = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('user_login_codes')->insert([
            'id' => $idB,
            'user_id' => $this->studentUser2->id,
            'code' => $codeB,
            'status' => 'active',
            'expires_at' => null,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        // Test D: Existing non-null expiry code (10 hours in future)
        $codeD = 'EXIST10H';
        $idD = (string) Str::ulid();
        $presetExpiry = now()->addHours(10)->startOfSecond();
        \Illuminate\Support\Facades\DB::table('user_login_codes')->insert([
            'id' => $idD,
            'user_id' => $this->studentUser1->id,
            'code' => $codeD,
            'status' => 'active',
            'expires_at' => $presetExpiry,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        // Test E: Used code with expires_at = NULL
        $codeE = 'USEDNULL';
        $idE = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('user_login_codes')->insert([
            'id' => $idE,
            'user_id' => $this->studentUser1->id,
            'code' => $codeE,
            'status' => 'used',
            'expires_at' => null,
            'created_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
        ]);

        // Instantiate and run the migration
        $migration = require database_path('migrations/2026_10_16_160000_backfill_expires_at_on_legacy_active_user_login_codes.php');
        $migration->up();

        // Verify Test A: expires_at < now() and authentication fails
        $rowA = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idA)->first();
        $this->assertNotNull($rowA->expires_at);
        $this->assertTrue(\Carbon\Carbon::parse($rowA->expires_at)->isPast());

        $authResponseA = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $codeA,
            'device_name' => 'Legacy Device A',
        ]);
        $authResponseA->assertStatus(422);
        $authResponseA->assertJsonValidationErrors(['code']);

        // Verify Test B: expires_at ≈ created_at + 24 hours (in the future) and authentication succeeds
        $rowB = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idB)->first();
        $this->assertNotNull($rowB->expires_at);
        $expectedExpiryB = \Carbon\Carbon::parse($rowB->created_at)->addHours(24);
        $this->assertEquals($expectedExpiryB->toDateTimeString(), \Carbon\Carbon::parse($rowB->expires_at)->toDateTimeString());
        $this->assertTrue(\Carbon\Carbon::parse($rowB->expires_at)->isFuture());

        $authResponseB = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => $codeB,
            'device_name' => 'Legacy Device B',
        ]);
        $authResponseB->assertStatus(200);
        $this->assertNotEmpty($authResponseB->json('access_token'));

        // Verify Test D: Existing non-null expiry was NOT touched
        $rowD = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idD)->first();
        $this->assertEquals($presetExpiry->toDateTimeString(), \Carbon\Carbon::parse($rowD->expires_at)->toDateTimeString());

        // Verify Test E: Used code remains with expires_at = NULL
        $rowE = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idE)->first();
        $this->assertNull($rowE->expires_at);

        // Test C: Idempotency (running migration twice does not alter already-fixed records)
        $migration->up();

        $rowASecond = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idA)->first();
        $rowBSecond = \Illuminate\Support\Facades\DB::table('user_login_codes')->where('id', $idB)->first();
        $this->assertEquals($rowA->expires_at, $rowASecond->expires_at);
        $this->assertEquals($rowB->expires_at, $rowBSecond->expires_at);
    }
}
