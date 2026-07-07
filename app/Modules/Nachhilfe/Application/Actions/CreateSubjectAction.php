<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Application\Events\SubjectCreated;

class CreateSubjectAction
{
    public function execute(string $name, ?string $description = null): SubjectModel
    {
        $subject = SubjectModel::create([
            'name' => $name,
            'description' => $description,
            'is_active' => true,
        ]);

        event(new SubjectCreated((string) $subject->id, (string) $subject->name));

        return $subject;
    }
}
