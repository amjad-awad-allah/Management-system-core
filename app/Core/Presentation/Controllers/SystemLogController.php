<?php

namespace App\Core\Presentation\Controllers;

use App\Core\Services\SystemLogService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogController extends Controller
{
    public function __construct(
        protected SystemLogService $systemLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'period' => $request->query('period', '24h'),
            'level' => $request->query('level', 'all'),
            'search' => $request->query('search', ''),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 50),
        ];

        $result = $this->systemLogService->getLogs($filters);

        return response()->json($result);
    }

    public function export(Request $request): StreamedResponse
    {
        $period = $request->query('period', '24h');
        $format = strtolower($request->query('format', 'txt'));
        if (!in_array($format, ['txt', 'json', 'log'])) {
            $format = 'txt';
        }

        $filters = [
            'period' => $period,
            'level' => $request->query('level', 'all'),
            'search' => $request->query('search', ''),
        ];

        $content = $this->systemLogService->exportFilteredLogs($filters, $format === 'json' ? 'json' : 'txt');
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $filename = "system_logs_{$period}_{$timestamp}." . ($format === 'json' ? 'json' : 'log');

        $contentType = $format === 'json' ? 'application/json' : 'text/plain';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure user is authorized to clear system logs (Admin / Super Admin)
        if (!$user || (!$user->hasRole('Super Admin') && !$user->hasRole('Admin') && !$user->can('manage settings'))) {
            return response()->json(['message' => 'Unauthorized to clear system logs.'], 403);
        }

        $scope = $request->input('scope', 'all');
        $result = $this->systemLogService->clearLogs($user, $scope);

        return response()->json([
            'message' => 'System logs cleared successfully.',
            'cleared' => $result,
        ]);
    }
}
