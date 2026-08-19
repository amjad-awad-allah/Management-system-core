<?php

namespace App\Modules\Nachhilfe\Application\Factories;

use App\Modules\Nachhilfe\Application\DTOs\PayrollReportData;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PayrollReportDataFactory
{
    private string $timezone = 'Europe/Berlin';

    public function create(string $payrollId): PayrollReportData
    {
        $payroll = TeacherPayroll::with(['teacher', 'approver', 'items.lesson.subject'])
            ->findOrFail($payrollId);

        if ($payroll->status !== 'approved') {
            throw ValidationException::withMessages([
                'payroll' => ['Cannot generate PDF report for unapproved payroll.'],
            ]);
        }

        // Recalculate canonical Merkle-style SHA-256 hash
        $itemsSorted = $payroll->items->sortBy(fn($item) => $item->lesson_id ?? $item->id);

        $itemTokens = [];
        foreach ($itemsSorted as $item) {
            $hStr = number_format((string) $item->hours, 2, '.', '');
            $rStr = number_format((string) $item->hourly_rate, 2, '.', '');
            $aStr = number_format((string) $item->amount, 2, '.', '');
            $lId = $item->lesson_id ?? $item->id;

            $itemTokens[] = "{$lId}:{$hStr}:{$rStr}:{$aStr}";
        }

        $totHoursStr = number_format((string) $payroll->total_hours, 2, '.', '');
        $totAmountStr = number_format((string) $payroll->total_amount, 2, '.', '');
        $approvedAtIso = $payroll->approved_at ? Carbon::parse($payroll->approved_at->toDateTimeString(), $this->timezone)->toIso8601String() : '';

        $canonicalString = implode('|', [
            'v1',
            $payroll->month,
            $payroll->teacher_id,
            (string) $payroll->total_completed_lessons,
            $totHoursStr,
            $totAmountStr,
            $approvedAtIso,
            implode(',', $itemTokens),
        ]);

        $recalculatedHash = hash('sha256', $canonicalString);

        if ($recalculatedHash !== $payroll->snapshot_hash) {
            throw ValidationException::withMessages([
                'tamper_gate' => ['Integrity Violation: Payroll snapshot hash mismatch detected. PDF generation blocked.'],
            ]);
        }

        $itemsData = [];
        foreach ($itemsSorted as $item) {
            $lesson = $item->lesson;
            $itemsData[] = [
                'lesson_id' => (string) ($item->lesson_id ?? $item->id),
                'date' => $lesson ? Carbon::parse($lesson->date)->format('d.m.Y') : 'N/A',
                'subject' => $lesson?->subject?->name ?? 'N/A',
                'hours' => number_format((string) $item->hours, 2, '.', ''),
                'hourly_rate' => number_format((string) $item->hourly_rate, 2, '.', ''),
                'amount' => number_format((string) $item->amount, 2, '.', ''),
            ];
        }

        return new PayrollReportData(
            payrollId: (string) $payroll->id,
            teacherId: (string) $payroll->teacher_id,
            teacherName: (string) ($payroll->teacher?->name ?? 'Unknown Teacher'),
            yearMonth: (string) $payroll->month,
            status: (string) $payroll->status,
            totalCompletedLessons: (string) $payroll->total_completed_lessons,
            totalHours: $totHoursStr,
            totalAmount: $totAmountStr,
            approvedAt: $payroll->approved_at ? Carbon::parse($payroll->approved_at)->format('d.m.Y H:i') : null,
            approverName: (string) ($payroll->approver?->name ?? 'System Admin'),
            snapshotHash: (string) $payroll->snapshot_hash,
            snapshotHashVersion: (int) ($payroll->snapshot_hash_version ?? 1),
            items: $itemsData,
            generatedAt: Carbon::now($this->timezone)->format('d.m.Y H:i'),
        );
    }
}
