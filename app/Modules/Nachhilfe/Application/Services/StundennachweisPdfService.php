<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Core\Services\CenterSettingsService;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class StundennachweisPdfService
{
    public function __construct(
        private readonly ?CenterSettingsService $centerSettings = null
    ) {}

    /**
     * Generate Stundennachweis PDF data.
     *
     * @param Student $student
     * @param string $month
     * @param Collection $attendances
     * @return string (Binary PDF data)
     */
    public function generate(Student $student, string $month, Collection $attendances): string
    {
        $settings = $this->centerSettings ?? app(CenterSettingsService::class);
        $center = [
            'name' => $settings->getCenterName(),
            'bundesland' => $settings->getCenterBundesland(),
            'logo_base64' => $settings->getCenterLogoBase64(),
        ];

        // Calculate total hours consumed
        $totalMinutes = $attendances->sum(function ($attendance) {
            return $attendance->lessonStudent?->lesson?->duration_minutes ?? 0;
        });
        $totalHours = round($totalMinutes / 60, 2);

        $pdf = Pdf::loadView('nachhilfe.stundennachweis', [
            'student' => $student,
            'month' => $month,
            'attendances' => $attendances,
            'totalHours' => $totalHours,
            'center' => $center,
        ]);

        // Standard Dompdf options for margins and page sizes
        $pdf->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true);

        return $pdf->output();
    }
}
