<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use App\Core\Models\UserLoginCode;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Support\Str;

class Phase1ASecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $centerManager;
    private User $receptionist;
    private User $teacherUser;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'Center Manager', 'guard_name' => 'web']);
        $receptionistRole = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage roles', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nachhilfe', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage students', 'guard_name' => 'web']);

        $managerRole->givePermissionTo(['manage users', 'manage roles', 'manage nachhilfe', 'manage students']);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->centerManager = User::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->centerManager->assignRole($managerRole);

        $this->receptionist = User::factory()->create([
            'email' => 'receptionist@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->receptionist->assignRole($receptionistRole);

        $this->teacherUser = User::factory()->create([
            'email' => 'teacher@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser->assignRole($teacherRole);

        $this->studentUser = User::factory()->create([
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->studentUser->assignRole($studentRole);
    }

    // ─────────────────────────────────────────────────────────────
    // 1. LoginCodeController: Privilege Escalation & Account Linking
    // ─────────────────────────────────────────────────────────────

    public function test_unauthorized_user_cannot_view_or_generate_student_or_teacher_login_code(): void
    {
        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Teacher',
        ]);

        // Unauthenticated
        $this->getJson("/api/v1/nachhilfe/students/{$student->id}/login-code")
            ->assertStatus(401);

        $this->getJson("/api/v1/nachhilfe/teachers/{$teacher->id}/login-code")
            ->assertStatus(401);

        // Unauthorized student
        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$student->id}/login-code")
            ->assertStatus(403);

        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$teacher->id}/login-code")
            ->assertStatus(403);
    }

    public function test_authorized_manager_can_view_student_and_teacher_login_code(): void
    {
        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'parent_email' => 'parent@example.com',
        ]);

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Teacher',
            'email' => 'unique_teacher@example.com',
        ]);

        $resStudent = $this->actingAs($this->centerManager, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$student->id}/login-code");
        $resStudent->assertStatus(200);
        $this->assertNotEmpty($resStudent->json('login_code.code'));

        $resTeacher = $this->actingAs($this->centerManager, 'sanctum')
            ->getJson("/api/v1/nachhilfe/teachers/{$teacher->id}/login-code");
        $resTeacher->assertStatus(200);
        $this->assertNotEmpty($resTeacher->json('login_code.code'));
    }

    public function test_student_and_teacher_creation_prevents_administrative_account_hijacking(): void
    {
        // A student is registered with the Super Admin's email
        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Hijack',
            'last_name' => 'Attempt',
            'parent_email' => $this->superAdmin->email,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/nachhilfe/students/{$student->id}/login-code");

        $response->assertStatus(200);

        // Refresh superAdmin from database
        $this->superAdmin->refresh();

        // The Super Admin MUST NOT have the Student role
        $this->assertFalse($this->superAdmin->hasRole('Student'));

        // The student MUST NOT be linked to the Super Admin user
        $student->refresh();
        $this->assertNotEquals($this->superAdmin->id, $student->user_id);
    }

    public function test_administrative_users_cannot_authenticate_via_mobile_short_code(): void
    {
        // Create an active login code for the Super Admin
        $loginCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->superAdmin->id,
            'code' => 'ADMIN123',
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        // Attempt mobile login
        $response = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'ADMIN123',
            'device_name' => 'Hacker Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);

        // Even if an admin user also has the Student role, mobile short code login MUST be denied
        $this->admin->assignRole('Student');
        $adminCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->admin->id,
            'code' => 'ADMIN456',
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        $response2 = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'ADMIN456',
            'device_name' => 'Hacker Device',
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['code']);
    }

    public function test_expired_or_used_login_code_is_rejected_and_single_use_enforced(): void
    {
        // 1. Expired code is rejected
        $expiredCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser->id,
            'code' => 'EXPIRED1',
            'status' => 'active',
            'expires_at' => now()->subMinute(),
        ]);

        $resExpired = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'EXPIRED1',
            'device_name' => 'Pixel 8',
        ]);
        $resExpired->assertStatus(422);
        $this->assertEquals('expired', $expiredCode->fresh()->status);

        // 2. Valid code succeeds
        $validCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser->id,
            'code' => 'VALID123',
            'status' => 'active',
            'expires_at' => now()->addHours(2),
        ]);

        $resValid = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'VALID123',
            'device_name' => 'Pixel 8',
        ]);
        $resValid->assertStatus(200);
        $resValid->assertJsonStructure(['access_token', 'user']);
        $this->assertEquals('used', $validCode->fresh()->status);

        // 3. Second use of same code is rejected (single-use enforcement)
        $resReuse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'VALID123',
            'device_name' => 'Pixel 8',
        ]);
        $resReuse->assertStatus(422);
    }

    public function test_center_manager_cannot_view_or_regenerate_admin_login_code(): void
    {
        // Center manager cannot view Admin login code
        $this->actingAs($this->centerManager, 'sanctum')
            ->getJson("/api/v1/users/{$this->admin->id}/login-code")
            ->assertStatus(403);

        // Center manager cannot regenerate Admin login code
        $this->actingAs($this->centerManager, 'sanctum')
            ->postJson("/api/v1/users/{$this->admin->id}/login-code/regenerate", [
                'admin_password' => 'password123',
            ])
            ->assertStatus(403);

        // Admin CAN view their own login code
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/users/{$this->admin->id}/login-code")
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. RoleController: Core System Role Protection
    // ─────────────────────────────────────────────────────────────

    public function test_core_system_roles_cannot_be_deleted(): void
    {
        $coreRoleNames = ['Super Admin', 'Admin', 'Center Manager', 'Teacher', 'Student', 'Receptionist'];

        foreach ($coreRoleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role);

            $response = $this->actingAs($this->superAdmin, 'sanctum')
                ->deleteJson("/api/v1/roles/{$role->id}");

            $response->assertStatus(400);
            $this->assertStringContainsString('Cannot delete', $response->json('message'));
        }
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $customRole = Role::create(['name' => 'Custom Counselor', 'guard_name' => 'web']);
        $this->studentUser->assignRole($customRole);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/v1/roles/{$customRole->id}");

        $response->assertStatus(400);
        $this->assertStringContainsString('Cannot delete role that currently has assigned users', $response->json('message'));
    }

    public function test_empty_custom_role_can_be_deleted(): void
    {
        $customRole = Role::create(['name' => 'Temporary Role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/v1/roles/{$customRole->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_core_system_roles_cannot_be_renamed(): void
    {
        $studentRole = Role::where('name', 'Student')->first();

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/v1/roles/{$studentRole->id}", [
                'name' => 'Learner',
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('Cannot rename core system role', $response->json('message'));
    }

    // ─────────────────────────────────────────────────────────────
    // 3. UserController: Privilege Escalation Prevention
    // ─────────────────────────────────────────────────────────────

    public function test_non_super_admin_cannot_assign_admin_or_super_admin_role(): void
    {
        // Center manager attempts to assign Super Admin
        $resSuper = $this->actingAs($this->centerManager, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Escalated User',
                'email' => 'escalated@example.com',
                'password' => 'secret1234',
                'roles' => ['Super Admin'],
            ]);
        $resSuper->assertStatus(403);

        // Center manager attempts to assign Admin
        $resAdmin = $this->actingAs($this->centerManager, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Escalated Admin',
                'email' => 'escalated_admin@example.com',
                'password' => 'secret1234',
                'roles' => ['Admin'],
            ]);
        $resAdmin->assertStatus(403);

        // Super Admin CAN assign Admin and Super Admin
        $resValid = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Legitimate Admin',
                'email' => 'legit_admin@example.com',
                'password' => 'secret1234',
                'roles' => ['Admin'],
            ]);
        $resValid->assertStatus(201);
    }

    public function test_center_manager_cannot_modify_or_delete_admin_account(): void
    {
        // Center manager attempts to modify Admin
        $this->actingAs($this->centerManager, 'sanctum')
            ->putJson("/api/v1/users/{$this->admin->id}", [
                'name' => 'Modified Admin',
            ])
            ->assertStatus(403);

        // Center manager attempts to delete Admin
        $this->actingAs($this->centerManager, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->admin->id}")
            ->assertStatus(403);

        // Super Admin CAN modify Admin
        $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/v1/users/{$this->admin->id}", [
                'name' => 'Updated Admin By Super',
            ])
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Rate Limiting on Credential Authentication Routes
    // ─────────────────────────────────────────────────────────────

    public function test_web_login_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'nobody@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt must be throttled with HTTP 429
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_mobile_password_login_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/mobile/login', [
                'email' => 'nobody@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt must be throttled with HTTP 429
        $response = $this->postJson('/api/v1/mobile/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }
}
