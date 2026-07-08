<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Application\Actions\CreateStudentAction;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Presentation\Requests\CreateStudentRequest;
use App\Modules\Nachhilfe\Presentation\Resources\StudentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController
{
    public function index(): AnonymousResourceCollection
    {
        $students = Student::paginate();
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
            grade: (int) $validated['grade']
        );

        return (new StudentResource($student))->response()->setStatusCode(201);
    }
}
