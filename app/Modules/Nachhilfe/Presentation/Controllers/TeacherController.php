<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Application\Actions\CreateTeacherAction;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Presentation\Requests\CreateTeacherRequest;
use App\Modules\Nachhilfe\Presentation\Resources\TeacherResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherController
{
    public function index(): AnonymousResourceCollection
    {
        $teachers = Teacher::paginate();
        return TeacherResource::collection($teachers);
    }

    public function store(CreateTeacherRequest $request, CreateTeacherAction $action): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $teacher = $action->execute(
            userId: $validated['user_id'],
            name: $validated['name'],
            qualification: $validated['qualification'],
            hourlyRate: (float) $validated['hourly_rate']
        );

        return (new TeacherResource($teacher))->response()->setStatusCode(201);
    }
}
