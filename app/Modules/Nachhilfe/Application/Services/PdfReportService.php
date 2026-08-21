<?php

namespace App\Modules\Nachhilfe\Application\Services;

use App\Core\Services\CenterSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\Nachhilfe\Application\DTOs\TeacherTimetableData;
use App\Modules\Nachhilfe\Application\DTOs\RoomDoorSheetData;
use App\Modules\Nachhilfe\Application\DTOs\PayrollReportData;

class PdfReportService
{
    public function __construct(
        private readonly ?CenterSettingsService $centerSettings = null
    ) {}

    /**
     * Get center branding array for PDF templates.
     */
    private function getCenterBranding(): array
    {
        $settings = $this->centerSettings ?? app(CenterSettingsService::class);
        return [
            'name' => $settings->getCenterName(),
            'bundesland' => $settings->getCenterBundesland(),
            'logo_base64' => $settings->getCenterLogoBase64(),
        ];
    }

    public function generateTeacherTimetable(TeacherTimetableData $dto): string
    {
        $center = $this->getCenterBranding();
        $pdf = Pdf::loadView('reports.teacher-timetable', [
            'data' => $dto->toArray(),
            'center' => $center,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Stundenplan - {$dto->teacherName}");
        $dompdf->add_info('Author', $center['name']);
        $dompdf->add_info('Subject', 'Wöchentlicher Stundenplan');
        $dompdf->add_info('Creator', $center['name'] . ' PDF Engine');

        return $pdf->output();
    }

    public function generateRoomDoorSheet(RoomDoorSheetData $dto): string
    {
        $center = $this->getCenterBranding();
        $pdf = Pdf::loadView('reports.room-door-sheet', [
            'data' => $dto->toArray(),
            'center' => $center,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Raumbelegung - {$dto->roomName}");
        $dompdf->add_info('Author', $center['name']);
        $dompdf->add_info('Subject', 'Raumbelegungsplan');
        $dompdf->add_info('Creator', $center['name'] . ' PDF Engine');

        return $pdf->output();
    }

    public function generatePayrollReport(PayrollReportData $dto): string
    {
        $center = $this->getCenterBranding();
        $pdf = Pdf::loadView('reports.payroll-report', [
            'data' => $dto->toArray(),
            'center' => $center,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Abrechnung - {$dto->teacherName} - {$dto->yearMonth}");
        $dompdf->add_info('Author', $center['name']);
        $dompdf->add_info('Subject', 'Honorar-Abrechnung');
        $dompdf->add_info('Creator', $center['name'] . ' PDF Engine');

        return $pdf->output();
    }
}
