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

        $normalizedStatus = strtolower((string) $payroll->status);
        if (!in_array($normalizedStatus, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages([
                'payroll' => ['Cannot generate PDF report for unapproved or unpaid payroll.'],
            ]);
        }

        if (empty($payroll->snapshot_hash)) {
            throw ValidationException::withMessages([
                'payroll' => ['Cannot generate PDF report: Payroll has no sealed snapshot hash. Legacy records require explicit re-sealing before report generation.'],
            ]);
        }

        // Centralized canonical Merkle-style SHA-256 hash recalculation and constant-time verification
        $recalculatedHash = $payroll->calculateSnapshotHash($this->timezone);

        if (!hash_equals((string) $payroll->snapshot_hash, $recalculatedHash)) {
            throw ValidationException::withMessages([
                'tamper_gate' => ['Integrity Violation: Payroll snapshot hash mismatch detected. PDF generation blocked.'],
            ]);
        }

        $itemsSorted = $payroll->items->sortBy(fn($item) => (string) ($item->lesson_id ?? $item->id));

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
            totalHours: number_format((float) $payroll->total_hours, 2, '.', ''),
            totalAmount: number_format((float) $payroll->total_amount, 2, '.', ''),
            approvedAt: $payroll->approved_at ? Carbon::parse($payroll->approved_at)->format('d.m.Y H:i') : null,
            approverName: (string) ($payroll->approver?->name ?? 'System Admin'),
            snapshotHash: (string) $payroll->snapshot_hash,
            snapshotHashVersion: (int) ($payroll->snapshot_hash_version ?? 1),
            items: $itemsData,
            generatedAt: Carbon::now($this->timezone)->format('d.m.Y H:i'),
        );
    }
}
