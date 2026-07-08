<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Application\Actions\CreateSubjectAction;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Presentation\Requests\CreateSubjectRequest;
use App\Modules\Nachhilfe\Presentation\Resources\SubjectResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SubjectController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', SubjectModel::class);

        $subjects = SubjectModel::query()->paginate();

        return SubjectResource::collection($subjects);
    }

    public function store(CreateSubjectRequest $request, CreateSubjectAction $action): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $subject = $action->execute(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            isActive: $validated['is_active'] ?? true
        );

        return (new SubjectResource($subject))->response()->setStatusCode(201);
    }
}
