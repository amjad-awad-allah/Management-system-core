<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Application\Actions\CreateStudentAction;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Presentation\Requests\CreateStudentRequest;
use App\Modules\Nachhilfe\Presentation\Resources\StudentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController
{
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $query = Student::with(['subjects', 'teachers']);
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone_1', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate();
        return StudentResource::collection($students);
    }

    public function show(string $id): StudentResource
    {
        $student = Student::withTrashed()->with(['subjects', 'teachers'])->findOrFail($id);
        return new StudentResource($student);
    }

    public function store(CreateStudentRequest $request, CreateStudentAction $action): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $student = $action->execute(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            birthDate: $validated['birth_date'],
            school: $validated['school'],
            grade: (int) $validated['grade'],
            parentPhone1: $validated['parent_phone_1'],
            parentPhone2: $validated['parent_phone_2'] ?? null,
            parentName: $validated['parent_name'] ?? null,
            parentEmail: $validated['parent_email'] ?? null,
            subjectIds: $validated['subject_ids'] ?? []
        );

        $student->load(['subjects', 'teachers']);
        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function update(\Illuminate\Http\Request $request, string $id): StudentResource
    {
        $student = Student::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'birth_date' => 'sometimes|required|date_format:Y-m-d',
            'school' => 'sometimes|required|string|max:255',
            'grade' => 'sometimes|required|integer|min:1|max:13',
            'parent_name' => 'sometimes|nullable|string|max:255',
            'parent_email' => 'sometimes|nullable|string|email|max:255',
            'parent_phone_1' => 'sometimes|required|string|max:20',
            'parent_phone_2' => 'nullable|string|max:20',
            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'string|exists:subjects,id',
        ]);

        $updateData = \Illuminate\Support\Arr::except($validated, ['subject_ids']);
        $student->update($updateData);

        if (isset($validated['parent_email'])) {
            $user = \App\Core\Models\User::firstOrCreate(
                ['email' => $validated['parent_email']],
                [
                    'name' => $validated['parent_name'] ?? ($student->first_name . ' Parent'),
                    'password' => \Illuminate\Support\Facades\Hash::make('password123') // Default password
                ]
            );
            
            if (!$user->hasRole('Student')) {
                // Ensure Student role exists or create it
                $role = \App\Core\Models\Role::firstOrCreate(['name' => 'Student']);
                $user->assignRole($role);
            }

            $student->update(['user_id' => $user->id]);
        }

        if ($request->has('subject_ids')) {
            $student->subjects()->sync($validated['subject_ids']);
        }

        return new StudentResource($student->load(['subjects', 'teachers']));
    }

    public function destroy(string $id): \Illuminate\Http\Response
    {
        $student = Student::findOrFail($id);
        $student->delete();
        
        return response()->noContent();
    }

    public function statement(string $id): \Illuminate\Http\JsonResponse
    {
        $student = Student::findOrFail($id);
        
        // Fetch lesson consumptions for this student
        $consumptions = \App\Modules\Nachhilfe\Infrastructure\Models\LessonConsumption::with(['lesson.subject', 'lesson.teacher', 'package.package'])
            ->whereHas('lessonStudent', function ($query) use ($id) {
                $query->where('student_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($consumptions);
    }
}
