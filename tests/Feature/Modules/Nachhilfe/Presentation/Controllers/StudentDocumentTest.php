<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentDocument;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();
    Storage::fake('local'); // Mock storage

    // Ensure settings
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    // Roles
    $this->adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
    $this->teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
    $this->parentRole = Role::firstOrCreate(['name' => 'Student']); // maps to Parent in this system
});

test('admin can upload, list, download, and delete student documents', function () {
    $adminUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'p']);
    $adminUser->assignRole($this->adminRole);

    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Test';
    $student->last_name = 'Student';
    $student->save();

    $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

    // 1. Upload
    $response = $this->actingAs($adminUser, 'sanctum')
        ->postJson("/api/v1/nachhilfe/students/{$student->id}/documents", [
            'title' => 'Voucher 2026',
            'category' => 'contract',
            'document_date' => '2026-07-01',
            'expires_at' => '2026-12-31',
            'file' => $file,
        ]);

    $response->assertStatus(201);
    $docId = $response->json('id');
    expect($docId)->not->toBeNull();

    // Verify storage
    Storage::assertExists($response->json('file_path'));

    // 2. List
    $response = $this->actingAs($adminUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$student->id}/documents");

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['title'])->toBe('Voucher 2026');

    // 3. Download
    $response = $this->actingAs($adminUser, 'sanctum')
        ->get("/api/v1/nachhilfe/documents/{$docId}/download");

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename="Voucher 2026.pdf"');

    // 4. Delete
    $response = $this->actingAs($adminUser, 'sanctum')
        ->deleteJson("/api/v1/nachhilfe/documents/{$docId}");

    $response->assertStatus(204);
    
    // Check DB deleted
    expect(StudentDocument::find($docId))->toBeNull();
});

test('parent isolation: parent can only access their own child documents', function () {
    // Parent A
    $parentUserA = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Parent A', 'email' => 'parenta@test.com', 'password' => 'p']);
    $parentUserA->assignRole($this->parentRole);

    $studentA = new Student();
    $studentA->id = (string) Str::ulid();
    $studentA->user_id = $parentUserA->id;
    $studentA->first_name = 'Child';
    $studentA->last_name = 'A';
    $studentA->save();

    // Student B (owned by another parent)
    $studentB = new Student();
    $studentB->id = (string) Str::ulid();
    $studentB->first_name = 'Child';
    $studentB->last_name = 'B';
    $studentB->save();

    // Seed document for Student B
    Storage::put('private/student-documents/b.pdf', 'dummy content');
    $docB = StudentDocument::create([
        'id' => (string) Str::ulid(),
        'student_id' => $studentB->id,
        'category' => 'contract',
        'title' => 'Contract B',
        'file_path' => 'private/student-documents/b.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'document_date' => '2026-07-01',
    ]);

    // Parent A tries to fetch Student B's documents list -> should fail
    $response = $this->actingAs($parentUserA, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$studentB->id}/documents");

    $response->assertStatus(403);

    // Parent A tries to download Student B's document -> should fail
    $response = $this->actingAs($parentUserA, 'sanctum')
        ->get("/api/v1/nachhilfe/documents/{$docB->id}/download");

    $response->assertStatus(403);
});

test('teacher document access: teachers can only download attendance_sheet of students they teach', function () {
    $teacherUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Teacher', 'email' => 'teacher@test.com', 'password' => 'p']);
    $teacherUser->assignRole($this->teacherRole);

    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'Teacher';
    $teacher->save();

    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Student';
    $student->last_name = 'A';
    $student->save();

    // 1. Create a lesson for them to establish the "teaches" relationship
    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert(['id' => $roomId, 'name' => 'R', 'is_active' => true]);
    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert(['id' => $subjectId, 'name' => 'S', 'code' => 'S', 'is_active' => true]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $teacher->id;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '15:00:00';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->save();

    // Documents
    Storage::put('private/student-documents/sheet.pdf', 'dummy content');
    Storage::put('private/student-documents/bescheid.pdf', 'dummy content');

    $sheetDoc = StudentDocument::create([
        'id' => (string) Str::ulid(),
        'student_id' => $student->id,
        'category' => 'attendance_sheet',
        'title' => 'Attendance Sheet July',
        'file_path' => 'private/student-documents/sheet.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'document_date' => '2026-07-01',
    ]);

    $bescheidDoc = StudentDocument::create([
        'id' => (string) Str::ulid(),
        'student_id' => $student->id,
        'category' => 'application',
        'title' => 'Jobcenter Bescheid',
        'file_path' => 'private/student-documents/bescheid.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'document_date' => '2026-07-01',
    ]);

    // Teacher tries to list documents -> should only return the attendance_sheet
    $response = $this->actingAs($teacherUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$student->id}/documents");

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['category'])->toBe('attendance_sheet');

    // Teacher downloads attendance_sheet -> allowed
    $response = $this->actingAs($teacherUser, 'sanctum')
        ->get("/api/v1/nachhilfe/documents/{$sheetDoc->id}/download");
    $response->assertStatus(200);

    // Teacher downloads application (bescheid) -> forbidden 403!
    $response = $this->actingAs($teacherUser, 'sanctum')
        ->get("/api/v1/nachhilfe/documents/{$bescheidDoc->id}/download");
    $response->assertStatus(403);
});

test('document uploads and deletions generate timeline audit events', function () {
    $adminUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'p']);
    $adminUser->assignRole($this->adminRole);

    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Test';
    $student->last_name = 'Student';
    $student->save();

    // Trigger upload
    $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');
    $response = $this->actingAs($adminUser, 'sanctum')
        ->postJson("/api/v1/nachhilfe/students/{$student->id}/documents", [
            'title' => 'Voucher 2026',
            'category' => 'contract',
            'document_date' => '2026-07-01',
            'expires_at' => '2026-12-31',
            'file' => $file,
        ]);
    
    $docId = $response->json('id');

    // Check timeline has the upload event
    $timelineResponse = $this->actingAs($adminUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$student->id}/timeline");

    $timelineResponse->assertStatus(200);
    $events = $timelineResponse->json('data');

    $uploadEvent = collect($events)->first(fn($e) => Str::contains($e['title'], 'Created (Document)'));
    expect($uploadEvent)->not->toBeNull();
    expect($uploadEvent['metadata']['new_values']['title'])->toBe('Voucher 2026');

    // Trigger delete
    $this->actingAs($adminUser, 'sanctum')
         ->deleteJson("/api/v1/nachhilfe/documents/{$docId}");

    // Check timeline has the delete event
    $timelineResponse = $this->actingAs($adminUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$student->id}/timeline");
    $events = $timelineResponse->json('data');

    $deleteEvent = collect($events)->first(fn($e) => Str::contains($e['title'], 'Deleted (Document)'));
    expect($deleteEvent)->not->toBeNull();
});
