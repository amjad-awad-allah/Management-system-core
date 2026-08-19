<?php

namespace App\Modules\Nachhilfe\Application\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\Nachhilfe\Application\DTOs\TeacherTimetableData;
use App\Modules\Nachhilfe\Application\DTOs\RoomDoorSheetData;
use App\Modules\Nachhilfe\Application\DTOs\PayrollReportData;

class PdfReportService
{
    public function generateTeacherTimetable(TeacherTimetableData $dto): string
    {
        $pdf = Pdf::loadView('reports.teacher-timetable', ['data' => $dto->toArray()])
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Stundenplan - {$dto->teacherName}");
        $dompdf->add_info('Author', 'Nachhilfe Management System');
        $dompdf->add_info('Subject', 'Wöchentlicher Stundenplan');
        $dompdf->add_info('Creator', 'Nachhilfe PDF Engine v1');

        return $pdf->output();
    }

    public function generateRoomDoorSheet(RoomDoorSheetData $dto): string
    {
        $pdf = Pdf::loadView('reports.room-door-sheet', ['data' => $dto->toArray()])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Raumbelegung - {$dto->roomName}");
        $dompdf->add_info('Author', 'Nachhilfe Management System');
        $dompdf->add_info('Subject', 'Raumbelegungsplan');
        $dompdf->add_info('Creator', 'Nachhilfe PDF Engine v1');

        return $pdf->output();
    }

    public function generatePayrollReport(PayrollReportData $dto): string
    {
        $pdf = Pdf::loadView('reports.payroll-report', ['data' => $dto->toArray()])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', "Abrechnung - {$dto->teacherName} - {$dto->yearMonth}");
        $dompdf->add_info('Author', 'Nachhilfe Management System');
        $dompdf->add_info('Subject', 'Honorar-Abrechnung');
        $dompdf->add_info('Creator', 'Nachhilfe PDF Engine v1');

        return $pdf->output();
    }
}
