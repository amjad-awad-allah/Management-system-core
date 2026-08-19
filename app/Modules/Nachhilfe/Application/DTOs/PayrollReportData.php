<?php

namespace App\Modules\Nachhilfe\Application\DTOs;

final class PayrollReportData
{
    /**
     * @param array<int, array{
     *     lesson_id: string,
     *     date: string,
     *     subject: string,
     *     hours: string,
     *     hourly_rate: string,
     *     amount: string
     * }> $items
     */
    public function __construct(
        public readonly string $payrollId,
        public readonly string $teacherId,
        public readonly string $teacherName,
        public readonly string $yearMonth,
        public readonly string $status,
        public readonly string $totalCompletedLessons,
        public readonly string $totalHours,
        public readonly string $totalAmount,
        public readonly ?string $approvedAt,
        public readonly ?string $approverName,
        public readonly string $snapshotHash,
        public readonly int $snapshotHashVersion,
        public readonly array $items,
        public readonly string $generatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'payroll_id' => $this->payrollId,
            'teacher_id' => $this->teacherId,
            'teacher_name' => $this->teacherName,
            'year_month' => $this->yearMonth,
            'status' => $this->status,
            'total_completed_lessons' => $this->totalCompletedLessons,
            'total_hours' => $this->totalHours,
            'total_amount' => $this->totalAmount,
            'approved_at' => $this->approvedAt,
            'approver_name' => $this->approverName,
            'snapshot_hash' => $this->snapshotHash,
            'snapshot_hash_version' => $this->snapshotHashVersion,
            'items' => $this->items,
            'generated_at' => $this->generatedAt,
        ];
    }
}
