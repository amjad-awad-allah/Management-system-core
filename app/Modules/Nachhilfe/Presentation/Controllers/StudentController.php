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
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $canManage = $user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])
            || $user->can('manage students');

        $query = Student::with(['subjects', 'teachers', 'packages']);

        if (!$canManage) {
            if ($user->hasRole('Teacher')) {
                $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
                if (!$teacher) {
                    abort(403, 'Teacher profile not found.');
                }
                $query->where(function ($q) use ($teacher) {
                    $q->whereHas('teachers', fn($t) => $t->where('teachers.id', $teacher->id))
                      ->orWhereHas('lessons', fn($l) => $l->where('teacher_id', $teacher->id));
                });
            } else {
                abort(403, 'Unauthorized access to student records.');
            }
        }
        
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone_1', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate($request->input('per_page', 15));
        return StudentResource::collection($students);
    }

    public function show(\Illuminate\Http\Request $request, string $id): StudentResource
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $student = Student::withTrashed()->with(['subjects', 'teachers', 'packages'])->findOrFail($id);

        $canManage = $user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])
            || $user->can('manage students');

        if (!$canManage) {
            if ($user->hasRole('Teacher')) {
                $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
                if (!$teacher) {
                    abort(403, 'Teacher profile not found.');
                }
                $teaches = $student->teachers()->where('teachers.id', $teacher->id)->exists()
                    || \App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent::where('student_id', $id)
                        ->whereHas('lesson', fn($q) => $q->where('teacher_id', $teacher->id))
                        ->exists();

                if (!$teaches) {
                    abort(403, 'You do not teach this student.');
                }
            } elseif ($user->hasRole('Student')) {
                if ($student->user_id !== $user->id) {
                    abort(403, 'Unauthorized access to student profile.');
                }
            } else {
                abort(403, 'Unauthorized access to student profile.');
            }
        }

        return new StudentResource($student);
    }

    public function store(CreateStudentRequest $request, CreateStudentAction $action): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist']) && !$user->can('manage students'))) {
            abort(403, 'Unauthorized to create students.');
        }

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

        $student->load(['subjects', 'teachers', 'packages']);
        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function update(\Illuminate\Http\Request $request, string $id): StudentResource
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist']) && !$user->can('manage students'))) {
            abort(403, 'Unauthorized to update students.');
        }

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
            $userModel = \App\Core\Models\User::firstOrCreate(
                ['email' => $validated['parent_email']],
                [
                    'name' => $validated['parent_name'] ?? ($student->first_name . ' Parent'),
                    'password' => \Illuminate\Support\Facades\Hash::make('password123') // Default password
                ]
            );
            
            if (!$userModel->hasRole('Student')) {
                // Ensure Student role exists or create it
                $role = \App\Core\Models\Role::firstOrCreate(['name' => 'Student']);
                $userModel->assignRole($role);
            }

            $student->update(['user_id' => $userModel->id]);
        }

        if ($request->has('subject_ids')) {
            $student->subjects()->sync($validated['subject_ids']);
        }

        return new StudentResource($student->load(['subjects', 'teachers', 'packages']));
    }

    public function destroy(\Illuminate\Http\Request $request, string $id): \Illuminate\Http\Response
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin', 'Center Manager']) && !$user->can('manage students'))) {
            abort(403, 'Unauthorized to delete students.');
        }

        $student = Student::findOrFail($id);
        $student->delete();
        
        return response()->noContent();
    }

    public function statement(\Illuminate\Http\Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $student = Student::findOrFail($id);

        $canManage = $user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])
            || $user->can('manage students') || $user->can('manage billing');

        if (!$canManage) {
            if ($user->hasRole('Student') && $student->user_id === $user->id) {
                // Allowed for student owner
            } else {
                abort(403, 'Unauthorized access to student statement.');
            }
        }
        
        // Fetch subscription usages for this student
        $usages = \App\Modules\Nachhilfe\Infrastructure\Models\SubscriptionUsage::with(['lesson.subject', 'lesson.teacher', 'subscription.package'])
            ->whereHas('subscription', function ($query) use ($id) {
                $query->where('student_id', $id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($usages);
    }

    public function timeline(string $id, \App\Modules\Nachhilfe\Application\Queries\GetStudentTimelineQuery $query, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $student = Student::findOrFail($id);

        $canManage = $user->hasRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])
            || $user->can('manage students');

        if (!$canManage) {
            if ($user->hasRole('Teacher')) {
                $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
                if (!$teacher) {
                    abort(403, 'Teacher profile not found.');
                }
                $teaches = $student->teachers()->where('teachers.id', $teacher->id)->exists()
                    || \App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent::where('student_id', $id)
                        ->whereHas('lesson', fn($q) => $q->where('teacher_id', $teacher->id))
                        ->exists();

                if (!$teaches) {
                    abort(403, 'You do not teach this student.');
                }
            } elseif ($user->hasRole('Student')) {
                if ($student->user_id !== $user->id) {
                    abort(403, 'Unauthorized access to student timeline.');
                }
            } else {
                abort(403, 'Unauthorized access to student timeline.');
            }
        }

        $page = (int) $request->input('page', 1);
        $type = $request->query('type');
        return response()->json($query->execute($student, $user, $page, 15, $type));
    }
}
