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
        $query = Teacher::with(['subjects', 'students']);
        
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('qualification', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        $teachers = $query->paginate();
        return TeacherResource::collection($teachers);
    }

    public function show(string $id): TeacherResource
    {
        $teacher = Teacher::with(['subjects', 'students'])->findOrFail($id);
        return new TeacherResource($teacher);
    }

    public function store(CreateTeacherRequest $request, CreateTeacherAction $action): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $teacher = $action->execute(
            userId: $validated['user_id'],
            name: $validated['name'],
            qualification: $validated['qualification'],
            hourlyRate: (float) $validated['hourly_rate'],
            subjectIds: $validated['subject_ids'] ?? []
        );

        $teacher->load(['subjects', 'students']);
        return (new TeacherResource($teacher))->response()->setStatusCode(201);
    }

    public function update(\Illuminate\Http\Request $request, string $id): TeacherResource
    {
        $teacher = Teacher::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|string|email|max:255',
            'phone' => 'sometimes|nullable|string|max:255',
            'qualification' => 'sometimes|required|string|max:255',
            'hourly_rate' => 'sometimes|required|numeric|min:0',
            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'string|exists:subjects,id',
        ]);

        $updateData = \Illuminate\Support\Arr::except($validated, ['subject_ids']);
        $teacher->update($updateData);

        if ($request->has('subject_ids')) {
            $teacher->subjects()->sync($validated['subject_ids']);
        }

        return new TeacherResource($teacher->load(['subjects', 'students']));
    }

    public function destroy(string $id): \Illuminate\Http\Response
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        
        return response()->noContent();
    }
}
