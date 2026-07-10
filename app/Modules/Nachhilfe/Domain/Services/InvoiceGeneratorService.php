<?php

namespace App\Modules\Nachhilfe\Domain\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvoiceGeneratorService
{
    /**
     * Generate an invoice for a student for a specific month.
     * @param Student $student
     * @param string $month Format: 'YYYY-MM'
     * @return Invoice|null Returns null if no billable lessons found.
     */
    public function generateForStudent(Student $student, string $month): ?Invoice
    {
        return DB::transaction(function () use ($student, $month) {
            // Find all attendances for the student in the given month that are billable (present or excused)
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            $lessonStudents = LessonStudent::with(['lesson.subject', 'attendance', 'student'])
                ->where('student_id', $student->id)
                ->whereHas('lesson', function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->whereBetween('date', [$startOfMonth, $endOfMonth])
                      ->where('status', 'completed'); // Only completed lessons are billed
                })
                ->whereHas('attendance', function ($q) {
                    // Assuming policy: we bill for 'present' and 'absent_unexcused'. 'absent_excused' is NOT billed.
                    $q->whereIn('status', ['present', 'absent_unexcused']);
                })
                ->get();

            if ($lessonStudents->isEmpty()) {
                return null;
            }

            $contracts = $student->contracts()->where('status', 'active')->get()->keyBy('subject_id');

            // Throw if a finalized invoice already exists
            $finalizedInvoice = Invoice::where('student_id', $student->id)
                ->where('month', $month)
                ->whereIn('status', ['unpaid', 'paid', 'partially_paid', 'void'])
                ->first();
            
            if ($finalizedInvoice) {
                throw new \DomainException('A finalized invoice already exists for this month.');
            }

            // Delete any existing DRAFT invoice for this month so we can regenerate
            $existingInvoice = Invoice::where('student_id', $student->id)
                ->where('month', $month)
                ->where('status', 'draft')
                ->first();

            if ($existingInvoice) {
                $existingInvoice->items()->delete();
                $existingInvoice->forceDelete();
            }

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'student_id' => $student->id,
                'month' => $month,
                'total_amount' => 0,
                'status' => 'draft',
                'due_date' => Carbon::createFromFormat('Y-m', $month)->endOfMonth()->addDays(14)->toDateString(),
            ]);

            $totalAmount = 0;

            foreach ($lessonStudents as $ls) {
                $lesson = $ls->lesson;
                $subjectId = $lesson->subject_id;
                
                $rate = 20.00; // Default fallback
                if ($contracts->has($subjectId)) {
                    $rate = $contracts->get($subjectId)->hourly_rate;
                }

                $hours = $lesson->duration_minutes / 60;
                $amount = $hours * $rate;
                $totalAmount += $amount;

                $invoice->items()->create([
                    'lesson_id' => $lesson->id,
                    'description' => "Lesson on {$lesson->date} ({$lesson->subject->name}) - Status: {$ls->attendance->status}",
                    'hours' => $hours,
                    'rate' => $rate,
                    'amount' => $amount,
                ]);
            }

            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice;
        });
    }
}
