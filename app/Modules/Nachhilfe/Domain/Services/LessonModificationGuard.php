<?php

namespace App\Modules\Nachhilfe\Domain\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Illuminate\Validation\ValidationException;

class LessonModificationGuard
{
    /**
     * Check if a lesson is attached to an APPROVED payroll record.
     *
     * @param Lesson $lesson
     * @throws ValidationException
     */
    public function checkCanModify(Lesson $lesson): void
    {
        $isAttachedToApprovedPayroll = $lesson->payrollItems()
            ->whereHas('payroll', function ($query) {
                $query->where('status', 'approved');
            })
            ->exists();

        if ($isAttachedToApprovedPayroll) {
            throw ValidationException::withMessages([
                'payroll' => ['Lesson belongs to an approved payroll and cannot be modified, rescheduled, or deleted.'],
            ]);
        }
    }
}
