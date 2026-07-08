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
        $query = Student::query();
        
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
        $student = Student::findOrFail($id);
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
            parentPhone2: $validated['parent_phone_2'] ?? null
        );

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
            'parent_phone_1' => 'sometimes|required|string|max:20',
            'parent_phone_2' => 'nullable|string|max:20',
        ]);

        $student->update($validated);

        return new StudentResource($student);
    }

    public function destroy(string $id): \Illuminate\Http\Response
    {
        $student = Student::findOrFail($id);
        $student->delete();
        
        return response()->noContent();
    }
}
