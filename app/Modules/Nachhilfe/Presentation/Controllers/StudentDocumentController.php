<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentDocument;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StudentDocumentController
{
    public function index(Request $request, string $studentId)
    {
        $user = auth()->user();

        // 1. Admin/Super Admin check
        if ($user->hasRole(['Admin', 'Super Admin'])) {
            $documents = StudentDocument::where('student_id', $studentId)->get();
            return response()->json($documents);
        }

        // 2. Parent check
        if ($user->hasRole('Student')) {
            $student = Student::where('id', $studentId)->where('user_id', $user->id)->first();
            if (!$student) {
                return response()->json(['message' => 'Unauthorized access to student documents.'], 403);
            }
            $documents = StudentDocument::where('student_id', $studentId)->get();
            return response()->json($documents);
        }

        // 3. Teacher check
        if ($user->hasRole('Teacher')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json(['message' => 'Teacher profile not found.'], 403);
            }

            // Check if teacher teaches this student
            $teaches = LessonStudent::where('student_id', $studentId)
                ->whereHas('lesson', fn($q) => $q->where('teacher_id', $teacher->id))
                ->exists();

            if (!$teaches) {
                return response()->json(['message' => 'You do not teach this student.'], 403);
            }

            // Teachers can only view attendance sheets
            $documents = StudentDocument::where('student_id', $studentId)
                ->where('category', 'attendance_sheet')
                ->get();

            return response()->json($documents);
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }

    public function store(Request $request, string $studentId)
    {
        $user = auth()->user();

        // Only admins can upload files for now (parents and teachers can be granted later if needed)
        if (!$user->hasRole(['Admin', 'Super Admin'])) {
            return response()->json(['message' => 'Only admins can upload student documents.'], 403);
        }

        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:application,extension,contract,invoice,attendance_sheet,other',
            'document_date' => 'required|date',
            'expires_at' => 'nullable|date|after_or_equal:document_date',
            'file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240', // 10MB limit
        ]);

        $file = $request->file('file');
        $path = $file->store('private/student-documents');

        $document = StudentDocument::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $user->id,
            'document_date' => $validated['document_date'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json($document, 201);
    }

    public function download(string $id)
    {
        $user = auth()->user();
        $document = StudentDocument::findOrFail($id);
        $studentId = $document->student_id;

        // 1. Admin/Super Admin check
        if ($user->hasRole(['Admin', 'Super Admin'])) {
            return Storage::download($document->file_path, $document->title . '.' . $this->getExtension($document->mime_type));
        }

        // 2. Parent check
        if ($user->hasRole('Student')) {
            $student = Student::where('id', $studentId)->where('user_id', $user->id)->first();
            if (!$student) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
            return Storage::download($document->file_path, $document->title . '.' . $this->getExtension($document->mime_type));
        }

        // 3. Teacher check
        if ($user->hasRole('Teacher')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Check if teacher teaches this student
            $teaches = LessonStudent::where('student_id', $studentId)
                ->whereHas('lesson', fn($q) => $q->where('teacher_id', $teacher->id))
                ->exists();

            if (!$teaches) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // Teachers can only download attendance sheets
            if ($document->category !== 'attendance_sheet') {
                return response()->json(['message' => 'Forbidden: Teachers cannot download financial or contract documents.'], 403);
            }

            return Storage::download($document->file_path, $document->title . '.' . $this->getExtension($document->mime_type));
        }

        return response()->json(['message' => 'Forbidden.'], 403);
    }

    public function destroy(string $id)
    {
        $user = auth()->user();

        // Only admins can delete files
        if (!$user->hasRole(['Admin', 'Super Admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $document = StudentDocument::findOrFail($id);
        
        // Delete the physical file
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    private function getExtension(string $mimeType): string
    {
        $map = [
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
        ];
        return $map[$mimeType] ?? 'bin';
    }
}
