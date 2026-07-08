<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Application\Actions\CreateTeacherAction;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Presentation\Requests\CreateTeacherRequest;
use App\Modules\Nachhilfe\Presentation\Resources\TeacherResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherController
{
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $query = Teacher::query();
        
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('qualification', 'like', "%{$search}%");
        }

        $teachers = $query->paginate();
        return TeacherResource::collection($teachers);
    }

    public function show(string $id): TeacherResource
    {
        $teacher = Teacher::findOrFail($id);
        return new TeacherResource($teacher);
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

    public function update(\Illuminate\Http\Request $request, string $id): TeacherResource
    {
        $teacher = Teacher::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'qualification' => 'sometimes|required|string|max:255',
            'hourly_rate' => 'sometimes|required|numeric|min:0',
        ]);

        $teacher->update($validated);

        return new TeacherResource($teacher);
    }

    public function destroy(string $id): \Illuminate\Http\Response
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        
        return response()->noContent();
    }
}
