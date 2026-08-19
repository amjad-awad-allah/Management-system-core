<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Application\Factories\TeacherTimetableDataFactory;
use App\Modules\Nachhilfe\Application\Factories\RoomDoorSheetDataFactory;
use App\Modules\Nachhilfe\Application\Factories\PayrollReportDataFactory;
use App\Modules\Nachhilfe\Application\Services\PdfReportService;
use App\Modules\Nachhilfe\Application\Services\PdfFilenameHelper;
use App\Modules\Nachhilfe\Infrastructure\Policies\PdfReportPolicy;
use App\Modules\Nachhilfe\Infrastructure\Models\PdfExportLog;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PdfReportController extends Controller
{
    public function teacherTimetable(
        Request $request,
        TeacherTimetableDataFactory $factory,
        PdfReportService $service,
        PdfReportPolicy $policy
    ) {
        $validated = $request->validate([
            'teacher_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $user = $request->user();
        $ip = $request->ip();

        if (!$policy->exportTeacherTimetable($user, $validated['teacher_id'])) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'teacher_timetable',
                'entity_id' => $validated['teacher_id'],
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => 'Unauthorized export attempt',
            ]);
            abort(403, 'Unauthorized to export teacher timetable.');
        }

        try {
            $dto = $factory->create($validated['teacher_id'], $validated['start_date'], $validated['end_date']);
            $pdfBinary = $service->generateTeacherTimetable($dto);
            $filename = PdfFilenameHelper::teacherTimetable($dto->teacherName, $validated['start_date']);

            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'teacher_timetable',
                'entity_id' => $validated['teacher_id'],
                'ip_address' => $ip,
                'success' => true,
            ]);

            return response($pdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'teacher_timetable',
                'entity_id' => $validated['teacher_id'],
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function roomDoorSheet(
        Request $request,
        RoomDoorSheetDataFactory $factory,
        PdfReportService $service,
        PdfReportPolicy $policy
    ) {
        $validated = $request->validate([
            'room_id' => 'required|string',
            'date' => 'required|date',
        ]);

        $user = $request->user();
        $ip = $request->ip();

        if (!$policy->exportRoomDoorSheet($user)) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'room_door_sheet',
                'entity_id' => $validated['room_id'],
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => 'Unauthorized export attempt',
            ]);
            abort(403, 'Unauthorized to export room door sheet.');
        }

        try {
            $dto = $factory->create($validated['room_id'], $validated['date']);
            $pdfBinary = $service->generateRoomDoorSheet($dto);
            $filename = PdfFilenameHelper::roomDoorSheet($dto->roomName, $validated['date']);

            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'room_door_sheet',
                'entity_id' => $validated['room_id'],
                'ip_address' => $ip,
                'success' => true,
            ]);

            return response($pdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'room_door_sheet',
                'entity_id' => $validated['room_id'],
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function payrollReport(
        string $payrollId,
        Request $request,
        PayrollReportDataFactory $factory,
        PdfReportService $service,
        PdfReportPolicy $policy
    ) {
        $user = $request->user();
        $ip = $request->ip();

        $payroll = TeacherPayroll::find($payrollId);

        if (!$policy->exportPayroll($user, $payroll)) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'payroll',
                'entity_id' => $payrollId,
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => 'Unauthorized export attempt',
            ]);
            abort(403, 'Unauthorized to export payroll report.');
        }

        try {
            $dto = $factory->create($payrollId);
            $pdfBinary = $service->generatePayrollReport($dto);
            $filename = PdfFilenameHelper::payrollReport($dto->teacherName, $dto->yearMonth);

            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'payroll',
                'entity_id' => $payrollId,
                'ip_address' => $ip,
                'success' => true,
            ]);

            return response($pdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            PdfExportLog::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'report_type' => 'payroll',
                'entity_id' => $payrollId,
                'ip_address' => $ip,
                'success' => false,
                'failure_reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
