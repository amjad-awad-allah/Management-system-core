<?php

namespace App\Modules\Nachhilfe\Application\Contracts;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;

interface TimelineProviderInterface
{
    /**
     * Get timeline events for the student.
     *
     * @param Student $student
     * @return array<array<string, mixed>>
     */
    public function getEvents(Student $student): array;
}
