<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Application\Events\SubjectCreated;
use Illuminate\Support\Str;

class CreateSubjectAction
{
    public function execute(string $name, ?string $description = null, bool $isActive = true): SubjectModel
    {
        $subject = SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
        ]);

        event(new SubjectCreated((string) $subject->id, (string) $subject->name));

        return $subject;
    }
}
