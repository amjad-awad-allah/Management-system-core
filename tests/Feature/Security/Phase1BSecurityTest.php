<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use App\Modules\Nachhilfe\Domain\Services\PayrollGeneratorService;
use App\Modules\Nachhilfe\Domain\Services\LessonModificationGuard;
use App\Modules\Nachhilfe\Application\Factories\PayrollReportDataFactory;
use App\Modules\Nachhilfe\Application\Services\PayrollCalculationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Phase1BSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Teacher $teacher;
    private Room $room;
    private SubjectModel $subject;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('module_settings')->updateOrInsert(
            ['module' => 'Nachhilfe'],
            ['id' => (string) Str::ulid(), 'enabled' => true]
        );

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage billing', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nachhilfe', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['manage billing', 'manage nachhilfe']);

        $this->admin = User::factory()->create([
            'email' => 'admin_payroll@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->admin->assignRole($adminRole);

        $teacherUser = User::factory()->create(['email' => 'teacher1@example.com']);
        $this->teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Herr Mueller',
            'email' => 'mueller@example.com',
            'hourly_rate' => 35.00,
        ]);

        $this->room = Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Room 101',
            'capacity' => 10,
        ]);

        $this->subject = SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mathematik',
        ]);
    }

    public function test_cannot_delete_approved_payroll()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
            'approved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$payroll->id}");

        $response->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete an approved, paid, or void payroll record.']);

        $this->assertDatabaseHas('teacher_payrolls', ['id' => $payroll->id]);
    }

    public function test_cannot_delete_paid_or_void_payroll()
    {
        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-05',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Paid',
        ]);

        $voidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-04',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Void',
        ]);

        $responsePaid = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$paidPayroll->id}");
        $responsePaid->assertStatus(409);

        $responseVoid = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$voidPayroll->id}");
        $responseVoid->assertStatus(409);

        $this->assertDatabaseHas('teacher_payrolls', ['id' => $paidPayroll->id]);
        $this->assertDatabaseHas('teacher_payrolls', ['id' => $voidPayroll->id]);
    }

    public function test_cannot_delete_sealed_payroll_even_if_status_is_non_standard()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-03',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Processing',
            'snapshot_hash' => hash('sha256', 'mock_seal_hash'),
            'approved_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$payroll->id}");

        $response->assertStatus(409);
        $this->assertDatabaseHas('teacher_payrolls', ['id' => $payroll->id]);
    }

    public function test_can_delete_unsealed_draft_payroll()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-10',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);

        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Draft',
        ]);

        $item = TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $draftPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.50,
            'hourly_rate' => 35.00,
            'amount' => 52.50,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/payrolls/{$draftPayroll->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Payroll deleted successfully']);

        $this->assertDatabaseMissing('teacher_payrolls', ['id' => $draftPayroll->id]);
        $this->assertDatabaseMissing('teacher_payroll_items', ['id' => $item->id]);
    }

    public function test_cannot_modify_financial_figures_of_approved_payroll()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
        ]);

        // Attempting to change total_amount from 210 to 500
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/nachhilfe/payrolls/{$payroll->id}", [
                'status' => 'Approved',
                'total_amount' => 500.00,
            ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'Cannot modify financial figures of an approved, paid, or void payroll.']);

        $payroll->refresh();
        $this->assertEquals(210.00, (float) $payroll->total_amount);
    }

    public function test_cannot_revert_approved_payroll_to_draft_or_processing()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
        ]);

        // Attempt reverting via updateStatus (PATCH)
        $responseStatus = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$payroll->id}/status", [
                'status' => 'Draft',
            ]);

        $responseStatus->assertStatus(422);

        // Attempt reverting via update (PUT)
        $responseUpdate = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/nachhilfe/payrolls/{$payroll->id}", [
                'status' => 'Processing',
                'total_amount' => 210.00,
            ]);

        $responseUpdate->assertStatus(422);

        $payroll->refresh();
        $this->assertEquals('Approved', $payroll->status);
    }

    public function test_cannot_change_status_of_terminal_paid_or_void_payroll()
    {
        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-05',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Paid',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$paidPayroll->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(422);
        $paidPayroll->refresh();
        $this->assertEquals('Paid', $paidPayroll->status);
    }

    public function test_can_legitimately_transition_approved_payroll_to_paid()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
        ]);
        $payroll->update(['snapshot_hash' => $payroll->calculateSnapshotHash()]);

        // Legitimate transition via updateStatus
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$payroll->id}/status", [
                'status' => 'Paid',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('payroll.status', 'Paid');

        $payroll->refresh();
        $this->assertEquals('Paid', $payroll->status);
    }

    public function test_cannot_transition_to_paid_if_snapshot_hash_is_missing()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
            'snapshot_hash' => null,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$payroll->id}/status", [
                'status' => 'Paid',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid status transition from Approved to Paid.']);
    }

    public function test_payroll_generator_service_refuses_to_regenerate_when_approved_payroll_exists()
    {
        TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-07-15',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);

        $service = app(PayrollGeneratorService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A finalized payroll already exists for this month.');

        $service->generateForTeacher($this->teacher, '2026-07');
    }

    public function test_cannot_replace_approved_payroll_via_manual_generation_endpoint()
    {
        $existingApproved = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls', [
                'teacher_id' => $this->teacher->id,
                'month' => '2026-07',
                'total_amount' => 999.00,
            ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'A finalized payroll already exists for this month.']);

        $this->assertDatabaseHas('teacher_payrolls', ['id' => $existingApproved->id]);
        $this->assertDatabaseMissing('teacher_payrolls', ['total_amount' => 999.00]);
    }

    public function test_cannot_replace_approved_payroll_via_auto_generation_endpoint()
    {
        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-07-15',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);

        $existingApproved = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls', [
                'teacher_id' => $this->teacher->id,
                'month' => '2026-07',
            ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'A finalized payroll already exists for this month.']);

        $this->assertEquals(1, TeacherPayroll::where('teacher_id', $this->teacher->id)->where('month', '2026-07')->count());
    }

    public function test_canonical_snapshot_hash_calculation_and_report_data_factory_verification()
    {
        $approvedAt = now()->subDays(5);
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 1,
            'total_hours' => 2.00,
            'total_amount' => 70.00,
            'status' => 'Processing',
            'approved_at' => null,
            'approved_by' => null,
            'snapshot_hash' => null,
            'snapshot_hash_version' => 1,
        ]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'duration_minutes' => 120,
            'status' => 'completed',
        ]);

        TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $payroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 2.00,
            'hourly_rate' => 35.00,
            'amount' => 70.00,
        ]);

        $payroll->approved_at = $approvedAt;
        $payroll->approved_by = $this->admin->id;
        $computedHash = $payroll->calculateSnapshotHash();
        $this->assertNotEmpty($computedHash);
        $payroll->snapshot_hash = $computedHash;
        $payroll->status = 'Approved';
        $payroll->save();

        // Verify PayrollReportDataFactory accepts it and recalculation validates
        $factory = app(PayrollReportDataFactory::class);
        $reportData = $factory->create($payroll->id);

        $this->assertEquals($computedHash, $reportData->snapshotHash);
        $this->assertEquals('2026-08', $reportData->yearMonth);
    }

    public function test_tampered_snapshot_hash_blocks_report_generation()
    {
        $approvedAt = now()->subDays(5);
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 0,
            'total_hours' => 0.00,
            'total_amount' => 0.00,
            'status' => 'Approved',
            'approved_at' => $approvedAt,
            'approved_by' => $this->admin->id,
            'snapshot_hash' => 'tampered_fake_hash_value',
            'snapshot_hash_version' => 1,
        ]);

        $factory = app(PayrollReportDataFactory::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Integrity Violation: Payroll snapshot hash mismatch detected.');

        $factory->create($payroll->id);
    }

    public function test_lesson_modification_guard_protects_lessons_in_both_approved_and_paid_payrolls()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-10',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'duration_minutes' => 120,
            'status' => 'completed',
        ]);

        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 1,
            'total_hours' => 2.00,
            'total_amount' => 70.00,
            'status' => 'Processing',
        ]);

        TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $paidPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 2.00,
            'hourly_rate' => 35.00,
            'amount' => 70.00,
        ]);

        $paidPayroll->approved_at = now();
        $paidPayroll->snapshot_hash = $paidPayroll->calculateSnapshotHash();
        $paidPayroll->status = 'Approved';
        $paidPayroll->save();
        $paidPayroll->status = 'Paid';
        $paidPayroll->save();

        $guard = app(LessonModificationGuard::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Lesson belongs to an approved payroll and cannot be modified, rescheduled, or deleted.');

        $guard->checkCanModify($lesson);
    }

    public function test_pdf_report_rejected_when_hash_missing_or_amount_tampered_or_invalid_hash_with_paid()
    {
        $factory = app(PayrollReportDataFactory::class);

        // 1. Approved but missing snapshot_hash -> rejected
        $p1 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'status' => 'Approved',
            'approved_at' => now(),
            'snapshot_hash' => null,
        ]);

        try {
            $factory->create($p1->id);
            $this->fail('Expected ValidationException when snapshot_hash is missing.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Payroll has no sealed snapshot hash', $e->getMessage());
        }

        // 2. Amount changed after hash calculation -> tamper violation
        $p2 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 0,
            'total_hours' => 0,
            'total_amount' => 100.00,
            'status' => 'Approved',
            'approved_at' => now(),
            'snapshot_hash' => null,
        ]);
        $p2->update(['snapshot_hash' => $p2->calculateSnapshotHash()]);
        // Direct DB update to simulate silent tampering
        DB::table('teacher_payrolls')->where('id', $p2->id)->update(['total_amount' => 250.00]);

        try {
            $factory->create($p2->id);
            $this->fail('Expected ValidationException on tampered amount.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Integrity Violation: Payroll snapshot hash mismatch detected.', $e->getMessage());
        }

        // 3. Paid payroll with invalid hash -> tamper violation
        $p3 = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 0,
            'total_hours' => 0,
            'total_amount' => 100.00,
            'status' => 'Paid',
            'approved_at' => now(),
            'snapshot_hash' => 'invalid_tampered_hash_on_paid',
        ]);

        try {
            $factory->create($p3->id);
            $this->fail('Expected ValidationException on tampered paid payroll.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Integrity Violation: Payroll snapshot hash mismatch detected.', $e->getMessage());
        }
    }

    public function test_cannot_transition_from_draft_directly_to_paid_and_unauthorized_user_denied()
    {
        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'total_amount' => 100.00,
            'status' => 'Draft',
        ]);

        // 1. Direct Draft -> Paid transition must be rejected with 422
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$draftPayroll->id}/status", [
                'status' => 'Paid',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid status transition from Draft to Paid.']);

        $draftPayroll->refresh();
        $this->assertEquals('Draft', $draftPayroll->status);

        // 2. Unauthorized user (e.g. Teacher without billing permission) -> 403 Forbidden
        $regularUser = User::factory()->create();
        $responseAuth = $this->actingAs($regularUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$draftPayroll->id}/status", [
                'status' => 'Approved',
            ]);

        $responseAuth->assertStatus(403);
    }

    public function test_generator_service_preserves_unknown_statuses_and_rejects_silent_deletion()
    {
        // Payroll with non-draft/unknown status
        $unknownPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-07',
            'total_amount' => 150.00,
            'status' => 'InReview',
        ]);

        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-07-15',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $service = app(PayrollGeneratorService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Cannot regenerate payroll: existing record has non-draft status 'InReview'.");

        $service->generateForTeacher($this->teacher, '2026-07');

        // Verify the record was preserved and not deleted
        $this->assertDatabaseHas('teacher_payrolls', ['id' => $unknownPayroll->id]);
    }

    public function test_lesson_modification_guard_allows_modification_for_void_payrolls()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-10',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $voidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-08',
            'status' => 'Draft',
        ]);

        TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $voidPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.00,
            'hourly_rate' => 35.00,
            'amount' => 35.00,
        ]);

        $voidPayroll->update(['status' => 'Void']);

        $guard = app(LessonModificationGuard::class);

        // Lessons attached to a Void payroll must NOT be locked
        $guard->checkCanModify($lesson);
        $this->assertTrue(true);
    }

    public function test_cannot_create_approved_or_paid_payroll_via_manual_generation_endpoint()
    {
        $responseApproved = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls', [
                'teacher_id' => $this->teacher->id,
                'month' => '2026-09',
                'total_amount' => 500.00,
                'status' => 'Approved',
            ]);

        $responseApproved->assertStatus(422);

        $responsePaid = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls', [
                'teacher_id' => $this->teacher->id,
                'month' => '2026-09',
                'total_amount' => 500.00,
                'status' => 'Paid',
            ]);

        $responsePaid->assertStatus(422);
    }

    public function test_void_approved_payroll_requires_elevated_role()
    {
        // A user with only 'manage billing' permission (not Super Admin or Center Manager)
        $billingOnlyUser = User::factory()->create(['email' => 'billing_only@example.com']);
        $billingRole = Role::firstOrCreate(['name' => 'Billing Clerk', 'guard_name' => 'web']);
        $billingRole->givePermissionTo('manage billing');
        $billingOnlyUser->assignRole($billingRole);

        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 4,
            'total_hours' => 6.00,
            'total_amount' => 210.00,
            'status' => 'Approved',
            'approved_at' => now()->subDays(2),
            'approved_by' => $this->admin->id,
        ]);
        $approvedPayroll->update(['snapshot_hash' => $approvedPayroll->calculateSnapshotHash()]);

        // Billing clerk attempts to void an approved payroll -> 403 Forbidden
        $responseDenied = $this->actingAs($billingOnlyUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$approvedPayroll->id}/status", [
                'status' => 'Void',
                'void_reason' => 'Administrative cancellation due to schedule conflict',
            ]);
        $responseDenied->assertStatus(403);

        // Super Admin attempts to void without reason -> 422 Unprocessable Entity
        $responseNoReason = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$approvedPayroll->id}/status", [
                'status' => 'Void',
            ]);
        $responseNoReason->assertStatus(422)
            ->assertJson(['message' => 'A valid void_reason is strictly required when voiding an approved payroll.']);

        // Super Admin attempts to void with valid reason -> Allowed (200)
        $responseAllowed = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$approvedPayroll->id}/status", [
                'status' => 'Void',
                'void_reason' => 'Legitimate administrative cancellation prior to payment disbursement',
            ]);
        $responseAllowed->assertStatus(200);

        $approvedPayroll->refresh();
        $this->assertEquals('Void', $approvedPayroll->status);
    }

    public function test_cannot_void_a_paid_payroll()
    {
        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-05',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Paid',
            'approved_at' => now()->subMonth(),
        ]);
        $paidPayroll->update(['snapshot_hash' => $paidPayroll->calculateSnapshotHash()]);

        // Super Admin attempts to void a paid payroll -> 422 Forbidden/Unprocessable
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$paidPayroll->id}/status", [
                'status' => 'Void',
                'void_reason' => 'Attempting post-disbursement void',
            ]);

        $response->assertStatus(422);
        $paidPayroll->refresh();
        $this->assertEquals('Paid', $paidPayroll->status);
    }

    public function test_migration_up_and_down_executes_cleanly_and_reverts_schema()
    {
        // 1. Create a payroll record with 'Approved' status under widened schema
        $testPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-11',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Approved',
        ]);

        $migration = require base_path('database/migrations/2026_10_27_200000_widen_teacher_payrolls_status_column.php');

        // Test running up()
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('teacher_payrolls', 'status'));

        // Test running down() without override: MUST be rejected by default to protect accounting integrity
        putenv('ALLOW_UNSAFE_PAYROLL_ROLLBACK');
        unset($_ENV['ALLOW_UNSAFE_PAYROLL_ROLLBACK']);
        try {
            $migration->down();
            $this->fail('Expected DomainException was not thrown when down() was called with Approved records.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString("Found 1 record(s) in 'Approved' status", $e->getMessage());
        }

        // Test running down() WITH explicit operator permission override
        putenv('ALLOW_UNSAFE_PAYROLL_ROLLBACK=true');
        $_ENV['ALLOW_UNSAFE_PAYROLL_ROLLBACK'] = 'true';

        $migration->down();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('teacher_payrolls', 'status'));

        // Clean up environment
        putenv('ALLOW_UNSAFE_PAYROLL_ROLLBACK');
        unset($_ENV['ALLOW_UNSAFE_PAYROLL_ROLLBACK']);

        // Verify data was safely converted to a valid enum state ('Processing') without loss
        $testPayroll->refresh();
        $this->assertEquals('Processing', $testPayroll->status);

        $migrationContent = file_get_contents(
            base_path('database/migrations/2026_10_27_200000_widen_teacher_payrolls_status_column.php')
        );

        $this->assertStringContainsString("enum('status', ['Draft', 'Processing', 'Paid', 'Void'])", $migrationContent);
    }

    public function test_cannot_transition_to_approved_without_valid_sealed_snapshot()
    {
        $processingPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Processing',
        ]);

        // Attempt direct patch to Approved without running formal approval sealing service -> 422
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$processingPayroll->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid status transition from Processing to Approved.']);
    }

    public function test_voiding_approved_payroll_records_entry_in_permanent_audit_logs_table()
    {
        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-06',
            'total_completed_lessons' => 2,
            'total_hours' => 3.00,
            'total_amount' => 105.00,
            'status' => 'Approved',
            'approved_at' => now()->subDay(),
            'approved_by' => $this->admin->id,
        ]);
        $approvedPayroll->update(['snapshot_hash' => $approvedPayroll->calculateSnapshotHash()]);

        $voidReason = 'Audit verification test for permanent log storage';

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/payrolls/{$approvedPayroll->id}/status", [
                'status' => 'Void',
                'void_reason' => $voidReason,
            ]);

        $response->assertStatus(200);

        // Verify permanent entry in audit_logs table
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payroll_voided',
            'auditable_type' => TeacherPayroll::class,
            'auditable_id' => $approvedPayroll->id,
            'user_id' => $this->admin->id,
        ]);

        $logEntry = DB::table('audit_logs')
            ->where('auditable_id', $approvedPayroll->id)
            ->where('event', 'payroll_voided')
            ->first();

        $this->assertNotNull($logEntry);
        $this->assertStringContainsString($voidReason, (string) $logEntry->new_values);
    }

    public function test_utc_timestamp_consistency_in_snapshot_hash_between_memory_and_database()
    {
        $approvedAt = now();
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => $approvedAt,
            'approved_by' => $this->admin->id,
        ]);

        $memoryHash = $payroll->calculateSnapshotHash();

        // Reload fresh from database (simulating reload across requests/engines)
        $freshPayroll = $payroll->fresh();
        $dbHash = $freshPayroll->calculateSnapshotHash();

        $this->assertTrue(hash_equals($memoryHash, $dbHash));
    }

    public function test_direct_orm_status_mutation_to_paid_from_draft_is_blocked_by_model_lifecycle_hook()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Draft',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid status transition from Draft to paid.');

        // Attempt direct ORM bypass: bypassing controller and calling save directly
        $payroll->status = 'paid';
        $payroll->save();
    }

    public function test_direct_orm_status_mutation_to_paid_without_valid_seal_throws_domain_exception()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
            // Deliberately omitted snapshot_hash: unsealed approved record
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid status transition from Approved to paid.');

        // Attempt direct write to paid without valid seal
        $payroll->status = 'paid';
        $payroll->save();
    }

    public function test_direct_orm_status_mutation_on_terminal_paid_or_void_payroll_throws_domain_exception()
    {
        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Paid',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid status transition from Paid to draft.');

        // Attempt direct write mutation on terminal paid record
        $paidPayroll->status = 'draft';
        $paidPayroll->save();
    }

    public function test_direct_orm_financial_figure_tampering_on_sealed_payroll_throws_domain_exception()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $payroll->snapshot_hash = $payroll->calculateSnapshotHash();
        $payroll->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Cannot modify financial figure 'total_amount' of an approved, paid, or void payroll.");

        // Direct modification of financial total on sealed record
        $payroll->total_amount = 999.00;
        $payroll->save();
    }

    public function test_direct_orm_snapshot_hash_tampering_throws_domain_exception()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $payroll->snapshot_hash = $payroll->calculateSnapshotHash();
        $payroll->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot tamper with or modify an existing snapshot_hash.');

        // Direct tampering with snapshot_hash
        $payroll->snapshot_hash = hash('sha256', 'forged_fake_hash');
        $payroll->save();
    }

    public function test_atomic_audit_log_and_status_update_rollback_on_failure()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $payroll->snapshot_hash = $payroll->calculateSnapshotHash();
        $payroll->save();

        $initialAuditCount = DB::table('audit_logs')->count();

        // Simulate a transaction failure: status save or follow-up fails inside the transaction
        try {
            DB::transaction(function () use ($payroll) {
                $lockedPayroll = TeacherPayroll::where('id', $payroll->id)->lockForUpdate()->firstOrFail();
                $lockedPayroll->status = 'Void';
                $lockedPayroll->save();

                // Intentionally trigger an exception to verify atomicity rollback
                throw new \RuntimeException('Simulated unexpected failure during voiding transaction');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Verify status rolled back to 'Approved'
        $payroll->refresh();
        $this->assertEquals('Approved', $payroll->status);

        // Verify no orphan audit log entry was committed
        $this->assertEquals($initialAuditCount, DB::table('audit_logs')->count());
    }

    public function test_cannot_delete_sealed_or_approved_payroll_via_orm_deleting_hook()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $payroll->snapshot_hash = $payroll->calculateSnapshotHash();
        $payroll->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete an approved, paid, or void payroll record.');

        // Direct Eloquent delete attempt on approved record must be blocked by deleting hook
        $payroll->delete();
    }

    public function test_approve_payroll_generates_sealed_record_atomically_without_null_hash_window()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-10-10',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $service = app(PayrollCalculationService::class);
        $summary = $service->approvePayroll('2026-10', $this->admin->id);

        $this->assertEquals('approved', $summary['status']);

        $approvedPayroll = TeacherPayroll::where('teacher_id', $this->teacher->id)
            ->where('month', '2026-10')
            ->first();

        $this->assertNotNull($approvedPayroll);
        $this->assertEquals('approved', strtolower((string) $approvedPayroll->status));
        $this->assertNotEmpty($approvedPayroll->snapshot_hash);
        $this->assertTrue($approvedPayroll->hasValidSeal());
    }

    public function test_bulk_eloquent_query_builder_delete_on_sealed_payrolls_is_blocked_by_custom_builder()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $payroll->snapshot_hash = $payroll->calculateSnapshotHash();
        $payroll->save();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot bulk delete sealed, approved, paid, or void payroll records.');

        // Mass deletion attempt via TeacherPayroll::where(...)->delete()
        TeacherPayroll::where('id', $payroll->id)->delete();
    }

    public function test_bulk_eloquent_query_builder_update_and_update_quietly_cannot_bypass_state_machine_or_tamper_figures()
    {
        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Draft',
        ]);

        // 1. Bulk query builder update attempting Draft -> paid bypass
        try {
            TeacherPayroll::where('id', $draftPayroll->id)->update(['status' => 'paid']);
            $this->fail('Expected DomainException on bulk update from Draft to paid.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Invalid status transition from Draft to paid.', $e->getMessage());
        }

        // 2. updateQuietly attempting Draft -> paid bypass (skips model events, caught by custom Builder)
        try {
            $draftPayroll->updateQuietly(['status' => 'paid']);
            $this->fail('Expected DomainException on updateQuietly from Draft to paid.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Invalid status transition from Draft to paid.', $e->getMessage());
        }

        // 3. Bulk query builder update attempting to tamper with total_amount on sealed payroll
        $sealedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $sealedPayroll->update(['snapshot_hash' => $sealedPayroll->calculateSnapshotHash()]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Cannot modify financial figure 'total_amount' of an approved, paid, or void payroll.");

        TeacherPayroll::where('id', $sealedPayroll->id)->update(['total_amount' => 9999.00]);
    }

    public function test_voiding_approved_payroll_with_null_actor_is_rejected_with_403()
    {
        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $approvedPayroll->update(['snapshot_hash' => $approvedPayroll->calculateSnapshotHash()]);

        $service = app(\App\Modules\Nachhilfe\Domain\Services\PayrollStateTransitionService::class);

        try {
            $service->transition($approvedPayroll, 'Void', null, [
                'void_reason' => 'Attempting to void without authenticated actor',
            ]);
            $this->fail('Expected 403 HttpException when actor is null on approved payroll void.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        $approvedPayroll->refresh();
        $this->assertEquals('Approved', $approvedPayroll->status);
    }

    public function test_payroll_items_of_sealed_payroll_cannot_be_created_modified_or_deleted_via_orm_or_builder()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-10-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $service = app(PayrollCalculationService::class);
        $service->approvePayroll('2026-10', $this->admin->id);

        $sealedPayroll = TeacherPayroll::where('teacher_id', $this->teacher->id)
            ->where('month', '2026-10')
            ->firstOrFail();
        $item = $sealedPayroll->items()->firstOrFail();

        // 1. Cannot modify item via ORM
        try {
            $item->update(['amount' => 999.00]);
            $this->fail('Expected DomainException when updating item of sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot modify payroll items belonging to an approved, paid, or void payroll.', $e->getMessage());
        }

        // 2. Cannot modify item via bulk Builder
        try {
            TeacherPayrollItem::where('id', $item->id)->update(['amount' => 999.00]);
            $this->fail('Expected DomainException when bulk updating item of sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot modify payroll items belonging to an approved, paid, or void payroll.', $e->getMessage());
        }

        // 3. Cannot delete item via ORM
        try {
            $item->delete();
            $this->fail('Expected DomainException when deleting item of sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot delete payroll items belonging to an approved, paid, or void payroll.', $e->getMessage());
        }

        // 4. Cannot delete item via bulk Builder
        try {
            TeacherPayrollItem::where('id', $item->id)->delete();
            $this->fail('Expected DomainException when bulk deleting item of sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot delete payroll items belonging to an approved, paid, or void payroll.', $e->getMessage());
        }

        // 5. Cannot inject new item into already-sealed payroll
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot add items to a sealed payroll record.');

        TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $sealedPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 5.00,
            'hourly_rate' => 35.00,
            'amount' => 175.00,
        ]);
    }

    public function test_service_transition_rolls_back_status_when_audit_log_insert_fails()
    {
        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $approvedPayroll->update(['snapshot_hash' => $approvedPayroll->calculateSnapshotHash()]);

        // Drop audit_logs table in test DB to force DB::table('audit_logs')->insert() inside PayrollStateTransitionService to fail
        \Illuminate\Support\Facades\Schema::drop('audit_logs');

        $service = app(\App\Modules\Nachhilfe\Domain\Services\PayrollStateTransitionService::class);

        try {
            $service->transition($approvedPayroll, 'Void', $this->admin, [
                'void_reason' => 'Testing real service transaction rollback on audit failure',
            ]);
            $this->fail('Expected database exception when audit_logs insert fails.');
        } catch (\Throwable $e) {
            // Expected failure on audit_logs insert
        }

        // Status MUST remain 'Approved' because status save and audit_logs insert share the same DB::transaction()
        $approvedPayroll->refresh();
        $this->assertEquals('Approved', $approvedPayroll->status);
    }

    public function test_cannot_tamper_with_approval_metadata_approved_at_approved_by_or_version_on_sealed_payroll()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 1,
            'total_hours' => 1.50,
            'total_amount' => 52.50,
            'status' => 'Approved',
            'approved_at' => now()->subDay(),
            'approved_by' => $this->admin->id,
            'snapshot_hash_version' => 1,
        ]);
        $payroll->update(['snapshot_hash' => $payroll->calculateSnapshotHash()]);

        // 1. Direct ORM mutation of approved_at must be rejected
        try {
            $payroll->approved_at = now()->addDay();
            $payroll->save();
            $this->fail('Expected DomainException when mutating approved_at on sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString("Cannot modify approval metadata 'approved_at'", $e->getMessage());
        }
        $payroll->refresh();

        // 2. Bulk Builder update of approved_at must be rejected
        try {
            TeacherPayroll::where('id', $payroll->id)->update(['approved_at' => now()->addDay()]);
            $this->fail('Expected DomainException on bulk update of approved_at.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString("Cannot modify approval metadata 'approved_at'", $e->getMessage());
        }

        // 3. Direct ORM mutation of approved_by must be rejected
        $otherUser = User::factory()->create();
        try {
            $payroll->approved_by = $otherUser->id;
            $payroll->save();
            $this->fail('Expected DomainException when mutating approved_by on sealed payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString("Cannot modify approval metadata 'approved_by'", $e->getMessage());
        }
        $payroll->refresh();

        // 4. Direct ORM mutation of snapshot_hash_version must be rejected
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Cannot modify approval metadata 'snapshot_hash_version' of a sealed payroll.");

        $payroll->snapshot_hash_version = 2;
        $payroll->save();
    }

    public function test_cannot_reassign_payroll_id_on_payroll_item_to_or_from_sealed_payroll()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-10-15',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Draft',
        ]);

        $draftItem = TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $draftPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.00,
            'hourly_rate' => 35.00,
            'amount' => 35.00,
        ]);

        // Preload cached relation pointing to Draft payroll
        $draftItem->load('payroll');

        $sealedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 0,
            'total_hours' => 0.00,
            'total_amount' => 0.00,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $sealedPayroll->update(['snapshot_hash' => $sealedPayroll->calculateSnapshotHash()]);

        // Attempt to move draft item into the sealed payroll (even with cached $draftItem->payroll = Draft)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot modify payroll items belonging to an approved, paid, or void payroll.');

        $draftItem->payroll_id = $sealedPayroll->id;
        $draftItem->save();
    }

    public function test_legacy_payroll_retains_exact_static_reference_sha256_snapshot_hash()
    {
        // Pre-computed static SHA-256 hex digest for canonical string:
        // "v1|2026-08|01J8TEACHER000000000000001|1|2.00|70.00|2026-08-31T12:00:00+00:00|01J8LESSON0000000000000001:2.00:35.00:70.00"
        $expectedStaticReferenceSha256 = '92f6cab57a9f0bd9d9e3a75f2a2e10ec76c95460e27ef071d7dc90f47a8e0aac';

        $legacyUser = User::factory()->create(['email' => 'legacy_teacher@example.com']);
        $legacyTeacher = Teacher::create([
            'id' => '01J8TEACHER000000000000001',
            'user_id' => $legacyUser->id,
            'name' => 'Legacy Teacher',
            'email' => 'legacy_teacher@example.com',
            'hourly_rate' => 35.00,
        ]);

        $legacyLesson = new Lesson([
            'teacher_id' => $legacyTeacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-15',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'duration_minutes' => 120,
            'status' => 'completed',
        ]);
        $legacyLesson->id = '01J8LESSON0000000000000001';
        $legacyLesson->save();

        $payroll = TeacherPayroll::create([
            'id' => '01J8PAYROLL000000000000001',
            'teacher_id' => $legacyTeacher->id,
            'month' => '2026-08',
            'total_completed_lessons' => 1,
            'total_hours' => 2.00,
            'total_amount' => 70.00,
            'status' => 'Processing',
            'snapshot_hash_version' => 1,
        ]);

        TeacherPayrollItem::create([
            'id' => '01J8ITEM000000000000000001',
            'payroll_id' => $payroll->id,
            'lesson_id' => $legacyLesson->id,
            'hours' => 2.00,
            'hourly_rate' => 35.00,
            'amount' => 70.00,
        ]);

        // Seal record using the pre-saved static reference hash
        $payroll->approved_at = \Carbon\Carbon::parse('2026-08-31T12:00:00+00:00');
        $payroll->approved_by = $this->admin->id;
        $payroll->snapshot_hash = $expectedStaticReferenceSha256;
        $payroll->status = 'Approved';
        $payroll->save();

        // Reload from DB and verify against the static reference hash without self-comparison
        $reloaded = TeacherPayroll::findOrFail($payroll->id);
        $this->assertSame($expectedStaticReferenceSha256, $reloaded->calculateSnapshotHash());
        $this->assertTrue($reloaded->hasValidSeal());
    }

    public function test_cannot_create_payroll_item_when_parent_is_approved_paid_void_or_has_approved_at_even_without_snapshot_hash()
    {
        $cases = [
            ['status' => 'Approved', 'approved_at' => null, 'snapshot_hash' => null],
            ['status' => 'Paid', 'approved_at' => null, 'snapshot_hash' => null],
            ['status' => 'Void', 'approved_at' => null, 'snapshot_hash' => null],
            ['status' => 'Draft', 'approved_at' => now(), 'snapshot_hash' => null],
        ];

        foreach ($cases as $idx => $attrs) {
            $lesson = Lesson::create([
                'id' => (string) Str::ulid(),
                'teacher_id' => $this->teacher->id,
                'room_id' => $this->room->id,
                'subject_id' => $this->subject->id,
                'type' => 'individual',
                'date' => '2026-09-1' . $idx,
                'start_time' => '10:00',
                'end_time' => '11:00',
                'duration_minutes' => 60,
                'status' => 'completed',
            ]);

            $payroll = TeacherPayroll::create(array_merge([
                'id' => (string) Str::ulid(),
                'teacher_id' => $this->teacher->id,
                'month' => '2026-0' . ($idx + 1),
                'total_completed_lessons' => 1,
                'total_hours' => 1.00,
                'total_amount' => 35.00,
            ], $attrs));

            try {
                TeacherPayrollItem::create([
                    'id' => (string) Str::ulid(),
                    'payroll_id' => $payroll->id,
                    'lesson_id' => $lesson->id,
                    'hours' => 1.00,
                    'hourly_rate' => 35.00,
                    'amount' => 35.00,
                ]);
                $this->fail("Expected DomainException when adding item to payroll case #{$idx} ({$attrs['status']}).");
            } catch (\DomainException $e) {
                $this->assertStringContainsString('Cannot add items to a sealed payroll record.', $e->getMessage());
            }
        }
    }

    public function test_eloquent_builder_insert_insert_or_ignore_and_upsert_on_payroll_items_block_sealed_or_approved_payrolls()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-10-20',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        $row = [
            'id' => (string) Str::ulid(),
            'payroll_id' => $approvedPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.00,
            'hourly_rate' => 35.00,
            'amount' => 35.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 1. Eloquent Builder insert() blocked
        try {
            TeacherPayrollItem::insert($row);
            $this->fail('Expected DomainException on TeacherPayrollItem::insert() into Approved payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot add items to a sealed payroll record.', $e->getMessage());
        }

        // 2. Eloquent Builder insertOrIgnore() blocked
        try {
            TeacherPayrollItem::insertOrIgnore($row);
            $this->fail('Expected DomainException on TeacherPayrollItem::insertOrIgnore() into Approved payroll.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cannot add items to a sealed payroll record.', $e->getMessage());
        }

        // 3. Eloquent Builder upsert() blocked
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot modify payroll items belonging to an approved, paid, or void payroll.');

        TeacherPayrollItem::upsert([$row], ['id'], ['amount']);
    }

    public function test_transition_service_enforces_general_authorization_on_all_transitions()
    {
        $payroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Draft',
        ]);

        $unauthorizedUser = User::factory()->create(['email' => 'unauth_transition@example.com']);
        $service = app(\App\Modules\Nachhilfe\Domain\Services\PayrollStateTransitionService::class);

        // 1. User without billing role/permission is rejected with 403 on Draft -> Processing
        try {
            $service->transition($payroll, 'Processing', $unauthorizedUser);
            $this->fail('Expected 403 HttpException when unauthorized user calls PayrollStateTransitionService::transition.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // 2. Null actor is rejected with 403 on Draft -> Processing
        try {
            $service->transition($payroll, 'Processing', null);
            $this->fail('Expected 403 HttpException when null actor calls PayrollStateTransitionService::transition.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        $payroll->refresh();
        $this->assertEquals('Draft', $payroll->status);

        // 3. Authorized user with 'manage billing' permission can transition Draft -> Processing and logs audit_logs entry
        $billingUser = User::factory()->create(['email' => 'billing_transition@example.com']);
        $billingRole = Role::firstOrCreate(['name' => 'Billing Staff', 'guard_name' => 'web']);
        $billingRole->givePermissionTo('manage billing');
        $billingUser->assignRole($billingRole);

        $updated = $service->transition($payroll, 'Processing', $billingUser);
        $this->assertEquals('Processing', $updated->status);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payroll_status_updated',
            'auditable_type' => TeacherPayroll::class,
            'auditable_id' => $payroll->id,
            'user_id' => $billingUser->id,
        ]);
    }

    public function test_destroy_endpoint_locks_rechecks_state_and_rolls_back_items_on_failure()
    {
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-10-22',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-10',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Draft',
        ]);

        $item = TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $draftPayroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.00,
            'hourly_rate' => 35.00,
            'amount' => 35.00,
        ]);

        // Register a temporary deleting listener that fails when deleting the parent payroll after items()->delete() ran
        TeacherPayroll::deleting(function (TeacherPayroll $p) use ($draftPayroll) {
            if ($p->id === $draftPayroll->id) {
                throw new \RuntimeException('Simulated failure during payroll deletion after items deletion');
            }
        });

        try {
            $this->actingAs($this->admin, 'sanctum')
                ->deleteJson("/api/v1/nachhilfe/payrolls/{$draftPayroll->id}");
        } catch (\Throwable $e) {
            // Expected simulated exception
        }

        // Because destroy() runs inside DB::transaction(), the deleted TeacherPayrollItem MUST be rolled back and preserved
        $this->assertDatabaseHas('teacher_payrolls', ['id' => $draftPayroll->id]);
        $this->assertDatabaseHas('teacher_payroll_items', ['id' => $item->id]);
    }

    public function test_three_twenty_minute_lessons_calculate_exact_pay_from_minutes_and_populate_totals_across_services()
    {
        $teacherUser = User::factory()->create(['email' => 'twenty_min_teacher@example.com']);
        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Frau Klein',
            'email' => 'twenty_min_teacher@example.com',
            'hourly_rate' => 30.00,
        ]);

        // Create three 20-minute completed lessons (total = 60 mins = 1.00 hour, €10.00 each = €30.00 total)
        for ($i = 1; $i <= 3; $i++) {
            Lesson::create([
                'id' => (string) Str::ulid(),
                'teacher_id' => $teacher->id,
                'room_id' => $this->room->id,
                'subject_id' => $this->subject->id,
                'type' => 'individual',
                'date' => "2026-10-0{$i}",
                'start_time' => '10:00',
                'end_time' => '10:20',
                'duration_minutes' => 20,
                'status' => 'completed',
            ]);
        }

        // 1. Verify PayrollGeneratorService
        $generator = app(PayrollGeneratorService::class);
        $draftPayroll = $generator->generateForTeacher($teacher, '2026-10');

        $this->assertNotNull($draftPayroll);
        $this->assertEquals(3, (int) $draftPayroll->total_completed_lessons);
        $this->assertEquals(1.00, (float) $draftPayroll->total_hours);
        $this->assertEquals(30.00, (float) $draftPayroll->total_amount);

        $draftItems = $draftPayroll->items()->get();
        $this->assertCount(3, $draftItems);
        foreach ($draftItems as $item) {
            $this->assertEquals(0.33, (float) $item->hours);
            // Pay is based on actual 20/60 * 30 = 10.00, NOT 0.33 * 30 = 9.90
            $this->assertEquals(10.00, (float) $item->amount);
        }
        $this->assertEquals((float) $draftPayroll->total_amount, round((float) $draftItems->sum('amount'), 2));

        // 2. Verify PayrollCalculationService::approvePayroll
        $calcService = app(PayrollCalculationService::class);
        $summary = $calcService->approvePayroll('2026-10', $this->admin->id);

        $this->assertEquals('approved', $summary['status']);
        $this->assertEquals(3, $summary['total_completed_lessons']);
        $this->assertEquals(1.00, $summary['total_center_hours']);
        $this->assertEquals(30.00, $summary['total_center_payout']);

        $approvedPayroll = TeacherPayroll::where('teacher_id', $teacher->id)
            ->where('month', '2026-10')
            ->firstOrFail();

        $this->assertEquals(3, (int) $approvedPayroll->total_completed_lessons);
        $this->assertEquals(1.00, (float) $approvedPayroll->total_hours);
        $this->assertEquals(30.00, (float) $approvedPayroll->total_amount);
        $this->assertEquals(30.00, round((float) $approvedPayroll->items()->sum('amount'), 2));
        $this->assertTrue($approvedPayroll->hasValidSeal());
    }

    public function test_approve_payroll_preserves_existing_drafts_when_blocked_by_unverified_lessons()
    {
        $completedLesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-05',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        // Past scheduled (unverified) lesson in the same month
        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-06',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $generator = app(PayrollGeneratorService::class);
        $existingDraft = $generator->generateForTeacher($this->teacher, '2026-08');
        $this->assertNotNull($existingDraft);

        $calcService = app(PayrollCalculationService::class);
        try {
            $calcService->approvePayroll('2026-08', $this->admin->id);
            $this->fail('Expected ValidationException when unverified lessons exist.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payroll', $e->errors());
        }

        // Existing draft payroll and its item must still exist untouched
        $this->assertDatabaseHas('teacher_payrolls', [
            'id' => $existingDraft->id,
            'status' => 'Draft',
        ]);
        $this->assertDatabaseHas('teacher_payroll_items', [
            'payroll_id' => $existingDraft->id,
            'lesson_id' => $completedLesson->id,
        ]);
    }

    public function test_cannot_override_total_amount_on_draft_payroll_with_items_to_contradict_items_sum()
    {
        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-09-10',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $generator = app(PayrollGeneratorService::class);
        $draftPayroll = $generator->generateForTeacher($this->teacher, '2026-09');
        $this->assertNotNull($draftPayroll);
        $this->assertEquals(35.00, (float) $draftPayroll->total_amount);

        // Attempting to change total_amount on a draft that has lesson items to a mismatched value (500.00 != 35.00)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/nachhilfe/payrolls/{$draftPayroll->id}", [
                'status' => 'Draft',
                'total_amount' => 500.00,
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot set total_amount to a value that does not match the sum of payroll items.']);

        $draftPayroll->refresh();
        $this->assertEquals(35.00, (float) $draftPayroll->total_amount);
    }

    public function test_approve_payroll_aborts_and_preserves_manual_draft_payroll_without_overwriting()
    {
        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-09-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        // Create a manual payroll draft via the controller endpoint (has explicit total_amount = 250.00 and 0 items)
        $createResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls', [
                'teacher_id' => $this->teacher->id,
                'month' => '2026-09',
                'total_amount' => 250.00,
                'status' => 'Draft',
            ]);

        $createResponse->assertStatus(201);
        $manualPayrollId = $createResponse->json('payroll.id');
        $this->assertNotEmpty($manualPayrollId);

        // Attempt monthly approval for 2026-09 -> must abort with clear actionable error and NOT delete the manual draft
        $approveResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/nachhilfe/payrolls/approve', [
                'month' => '2026-09',
            ]);

        $approveResponse->assertStatus(422)
            ->assertJsonValidationErrors(['payroll']);

        $errorMessage = $approveResponse->json('errors.payroll.0');
        $this->assertStringContainsString('manual payroll draft exists', (string) $errorMessage);

        // Verify the manual draft remains completely unchanged in the database
        $this->assertDatabaseHas('teacher_payrolls', [
            'id' => $manualPayrollId,
            'teacher_id' => $this->teacher->id,
            'month' => '2026-09',
            'total_amount' => 250.00,
            'status' => 'Draft',
        ]);
        $this->assertEquals(1, TeacherPayroll::where('month', '2026-09')->count());
    }

    public function test_deleting_or_force_deleting_lesson_linked_to_approved_or_paid_payroll_is_rejected()
    {
        $approvedLesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-04-10',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);

        $calcService = app(PayrollCalculationService::class);
        $calcService->approvePayroll('2026-04', $this->admin->id);

        $approvedPayroll = TeacherPayroll::where('teacher_id', $this->teacher->id)
            ->where('month', '2026-04')
            ->firstOrFail();
        $approvedItem = $approvedPayroll->items()->firstOrFail();

        // 1. Soft delete on lesson linked to Approved payroll must be rejected
        try {
            $approvedLesson->delete();
            $this->fail('Expected ValidationException when soft-deleting lesson attached to Approved payroll.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payroll', $e->errors());
        }

        // 2. Force delete on lesson linked to Approved payroll must be rejected
        try {
            $approvedLesson->forceDelete();
            $this->fail('Expected ValidationException when force-deleting lesson attached to Approved payroll.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payroll', $e->errors());
        }

        // Transition payroll to Paid and verify both delete() and forceDelete() are also rejected
        $approvedPayroll->update(['status' => 'Paid']);

        try {
            $approvedLesson->delete();
            $this->fail('Expected ValidationException when soft-deleting lesson attached to Paid payroll.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payroll', $e->errors());
        }

        try {
            $approvedLesson->forceDelete();
            $this->fail('Expected ValidationException when force-deleting lesson attached to Paid payroll.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payroll', $e->errors());
        }

        // Both lesson and payroll item remain intact and not soft-deleted
        $this->assertNotSoftDeleted('lessons', ['id' => $approvedLesson->id]);
        $this->assertDatabaseHas('teacher_payroll_items', ['id' => $approvedItem->id]);
    }

    public function test_safe_lesson_deletion_behavior_remains_unchanged()
    {
        // 1. Unattached lesson can be soft-deleted
        $unattachedLesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-04-12',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $this->assertTrue((bool) $unattachedLesson->delete());
        $this->assertSoftDeleted('lessons', ['id' => $unattachedLesson->id]);

        // 2. Lesson attached to a Draft payroll can be soft-deleted
        $draftLesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-04-13',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);
        $draftPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-04',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Draft',
        ]);
        TeacherPayrollItem::create([
            'id' => (string) Str::ulid(),
            'payroll_id' => $draftPayroll->id,
            'lesson_id' => $draftLesson->id,
            'hours' => 1.00,
            'hourly_rate' => 35.00,
            'amount' => 35.00,
        ]);
        $this->assertTrue((bool) $draftLesson->delete());
        $this->assertSoftDeleted('lessons', ['id' => $draftLesson->id]);

        // 3. Lesson attached to a Void payroll can be soft-deleted
        $voidLesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-05-14',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed',
        ]);
        $calcService = app(PayrollCalculationService::class);
        $calcService->approvePayroll('2026-05', $this->admin->id);
        $voidPayroll = TeacherPayroll::where('teacher_id', $this->teacher->id)
            ->where('month', '2026-05')
            ->firstOrFail();
        $voidPayroll->update(['status' => 'Void']);

        $this->assertTrue((bool) $voidLesson->delete());
        $this->assertSoftDeleted('lessons', ['id' => $voidLesson->id]);
    }

    public function test_force_deleting_teacher_with_protected_payroll_records_is_rejected()
    {
        $approvedPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-04',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $approvedPayroll->update(['snapshot_hash' => $approvedPayroll->calculateSnapshotHash()]);

        try {
            $this->teacher->forceDelete();
            $this->fail('Expected DomainException when force-deleting a teacher with protected payroll records.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString(
                'Cannot force delete a teacher with approved, paid, void, or sealed payroll records.',
                $e->getMessage()
            );
        }

        // Verify neither the teacher nor the protected payroll record was deleted
        $this->assertDatabaseHas('teachers', ['id' => $this->teacher->id]);
        $this->assertDatabaseHas('teacher_payrolls', [
            'id' => $approvedPayroll->id,
            'status' => 'Approved',
        ]);
    }

    public function test_normal_teacher_soft_deletion_retains_existing_behavior_with_protected_payrolls()
    {
        $paidPayroll = TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'month' => '2026-04',
            'total_completed_lessons' => 1,
            'total_hours' => 1.00,
            'total_amount' => 35.00,
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);
        $paidPayroll->update(['snapshot_hash' => $paidPayroll->calculateSnapshotHash()]);
        $paidPayroll->update(['status' => 'Paid']);

        // Normal soft delete succeeds and does not cascade-delete protected payroll records
        $this->assertTrue((bool) $this->teacher->delete());
        $this->assertSoftDeleted('teachers', ['id' => $this->teacher->id]);
        $this->assertDatabaseHas('teacher_payrolls', [
            'id' => $paidPayroll->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'Paid',
        ]);
    }

    public function test_seeded_approved_payroll_passes_hash_verification_and_exports_pdf()
    {
        $this->seed(\Database\Seeders\NachhilfeModuleSeeder::class);

        $seededPayrolls = TeacherPayroll::with(['items.lesson.subject', 'teacher'])->get();
        $this->assertCount(3, $seededPayrolls);

        // 1. Verify all seeded payrolls are internally consistent with their persisted items and lessons
        foreach ($seededPayrolls as $payroll) {
            $items = $payroll->items()->with('lesson')->get();
            $this->assertGreaterThan(0, $items->count());
            $this->assertEquals((int) $payroll->total_completed_lessons, $items->count());
            $this->assertEquals(round((float) $payroll->total_hours, 2), round((float) $items->sum('hours'), 2));
            $this->assertEquals(round((float) $payroll->total_amount, 2), round((float) $items->sum('amount'), 2));

            foreach ($items as $item) {
                $this->assertNotNull($item->lesson);
                $this->assertEquals($payroll->teacher_id, $item->lesson->teacher_id);
                $this->assertEquals('completed', $item->lesson->status);
                $this->assertEquals($payroll->month, \Carbon\Carbon::parse($item->lesson->date)->format('Y-m'));
                $expectedHours = round($item->lesson->duration_minutes / 60, 2);
                $expectedRate = round((float) $payroll->teacher->hourly_rate, 2);
                $expectedAmount = round(($item->lesson->duration_minutes / 60) * $expectedRate, 2);
                $this->assertEquals($expectedHours, round((float) $item->hours, 2));
                $this->assertEquals($expectedRate, round((float) $item->hourly_rate, 2));
                $this->assertEquals($expectedAmount, round((float) $item->amount, 2));
            }
        }

        // 2. Reload the seeded Approved payroll fresh from the database and verify its canonical SHA-256 snapshot hash
        $approvedPayroll = TeacherPayroll::where('status', 'Approved')->firstOrFail()->fresh(['items.lesson.subject', 'teacher', 'approver']);
        $this->assertNotEmpty($approvedPayroll->snapshot_hash);
        $this->assertTrue($approvedPayroll->hasValidSeal());

        $seededItem = $approvedPayroll->items->first();
        $this->assertNotNull($seededItem);
        $expectedCanonicalPayload = implode('|', [
            'v1',
            (string) $approvedPayroll->month,
            (string) $approvedPayroll->teacher_id,
            '1',
            '1.50',
            '45.00',
            \Carbon\Carbon::parse($approvedPayroll->approved_at)->utc()->toIso8601String(),
            "{$seededItem->lesson_id}:1.50:30.00:45.00",
        ]);
        $this->assertSame(hash('sha256', $expectedCanonicalPayload), $approvedPayroll->snapshot_hash);

        // 3. Verify PayrollReportDataFactory accepts the valid snapshot and populates summary and detail data
        $factory = app(PayrollReportDataFactory::class);
        $reportDto = $factory->create($approvedPayroll->id);

        $this->assertSame($approvedPayroll->id, $reportDto->payrollId);
        $this->assertSame('Frau Sarah Weber', $reportDto->teacherName);
        $this->assertSame('1', $reportDto->totalCompletedLessons);
        $this->assertSame('1.50', $reportDto->totalHours);
        $this->assertSame('45.00', $reportDto->totalAmount);
        $this->assertSame($approvedPayroll->snapshot_hash, $reportDto->snapshotHash);
        $this->assertCount(1, $reportDto->items);
        $this->assertSame($seededItem->lesson_id, $reportDto->items[0]['lesson_id']);
        $this->assertSame('German', $reportDto->items[0]['subject']);
        $this->assertSame('1.50', $reportDto->items[0]['hours']);
        $this->assertSame('30.00', $reportDto->items[0]['hourly_rate']);
        $this->assertSame('45.00', $reportDto->items[0]['amount']);

        // 4. Verify rendered report template and HTTP PDF export succeed with summary and detail data
        $renderedHtml = view('reports.payroll-report', [
            'data' => $reportDto->toArray(),
            'center' => ['name' => 'Nachhilfe Zentrum', 'bundesland' => 'NW', 'logo_base64' => null],
        ])->render();
        $this->assertStringContainsString('Frau Sarah Weber', $renderedHtml);
        $this->assertStringContainsString('German', $renderedHtml);
        $this->assertStringContainsString('30.00 €', $renderedHtml);
        $this->assertStringContainsString('45.00 €', $renderedHtml);
        $this->assertStringContainsString($approvedPayroll->snapshot_hash, $renderedHtml);

        $seededAdmin = User::where('email', 'admin@admin.com')->firstOrFail();
        $response = $this->actingAs($seededAdmin, 'sanctum')
            ->getJson("/api/v1/nachhilfe/reports/payroll/{$approvedPayroll->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }
}
