<?php

namespace App\Core\Services;

use App\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemLogService
{
    public function __construct(
        protected CorrelationContext $context
    ) {}

    /**
     * Fetch filtered and paginated system logs.
     */
    public function getLogs(array $filters = []): array
    {
        $period = $filters['period'] ?? '24h';
        $levelFilter = strtolower($filters['level'] ?? 'all');
        $search = strtolower(trim($filters['search'] ?? ''));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 50)));

        $cutoff = $this->getPeriodCutoff($period);
        $logFiles = $this->getRelevantLogFiles($cutoff);

        $entries = [];

        foreach ($logFiles as $filePath) {
            if (!File::exists($filePath)) {
                continue;
            }

            $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            // Read lines in reverse (newest first)
            $lines = array_reverse($lines);

            foreach ($lines as $lineIndex => $line) {
                $entry = $this->parseLogLine($line, $filePath, $lineIndex);
                if (!$entry) {
                    continue;
                }

                // Filter by cutoff timestamp
                if ($cutoff && isset($entry['timestamp'])) {
                    try {
                        $entryTime = Carbon::parse($entry['timestamp']);
                        if ($entryTime->lt($cutoff)) {
                            continue;
                        }
                    } catch (\Throwable) {
                        // Skip if timestamp cannot be parsed
                    }
                }

                // Filter by level
                if (!$this->matchesLevel($entry['level'], $levelFilter)) {
                    continue;
                }

                // Filter by search query
                if ($search !== '' && !$this->matchesSearch($entry, $search)) {
                    continue;
                }

                $entries[] = $entry;
            }
        }

        // Sort entries newest first
        usort($entries, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($entries, $offset, $perPage);

        return [
            'data' => $paginatedData,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
            'statistics' => $this->getStatistics(),
        ];
    }

    /**
     * Calculate 24-hour log metrics and storage stats.
     */
    public function getStatistics(): array
    {
        $cutoff24h = Carbon::now()->subHours(24);
        $logFiles = $this->getRelevantLogFiles($cutoff24h);

        $criticalCount = 0;
        $errorsOnlyCount = 0;
        $warningsCount = 0;
        $infoCount = 0;
        $totalStorageBytes = 0;

        // Calculate storage for all log files
        $allFiles = File::glob(storage_path('logs/*.log')) ?: [];
        foreach ($allFiles as $file) {
            $totalStorageBytes += @filesize($file) ?: 0;
        }

        foreach ($logFiles as $filePath) {
            if (!File::exists($filePath)) {
                continue;
            }

            $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $lineIndex => $line) {
                $entry = $this->parseLogLine($line, $filePath, $lineIndex);
                if (!$entry) {
                    continue;
                }

                if (isset($entry['timestamp'])) {
                    try {
                        $entryTime = Carbon::parse($entry['timestamp']);
                        if ($entryTime->lt($cutoff24h)) {
                            continue;
                        }
                    } catch (\Throwable) {
                        continue;
                    }
                }

                $lvl = strtoupper($entry['level'] ?? '');
                if (in_array($lvl, ['CRITICAL', 'ALERT', 'EMERGENCY'])) {
                    $criticalCount++;
                } elseif ($lvl === 'ERROR') {
                    $errorsOnlyCount++;
                } elseif ($lvl === 'WARNING') {
                    $warningsCount++;
                } elseif (in_array($lvl, ['INFO', 'NOTICE'])) {
                    $infoCount++;
                }
            }
        }

        return [
            'critical_24h' => $criticalCount,
            'errors_only_24h' => $errorsOnlyCount,
            'total_errors_24h' => $criticalCount + $errorsOnlyCount,
            'warnings_24h' => $warningsCount,
            'info_24h' => $infoCount,
            'storage_bytes' => $totalStorageBytes,
            'storage_formatted' => $this->formatBytes($totalStorageBytes),
        ];
    }

    /**
     * Export the filtered logs as TXT/LOG or JSON stream.
     */
    public function exportFilteredLogs(array $filters, string $format = 'txt'): string
    {
        $filters['page'] = 1;
        $filters['per_page'] = 10000; // max export limit
        $result = $this->getLogs($filters);
        $logs = $result['data'];

        if ($format === 'json') {
            return json_encode([
                'exported_at' => Carbon::now()->toIso8601String(),
                'filters' => $filters,
                'total_count' => count($logs),
                'logs' => $logs,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Formatted TXT / LOG export
        $output = "===============================================================================\n";
        $output .= "BBP ERP - SYSTEM & ERROR LOG EXPORT\n";
        $output .= "Exported At: " . Carbon::now()->toIso8601String() . "\n";
        $output .= "Period Filter: " . ($filters['period'] ?? '24h') . " | Level: " . ($filters['level'] ?? 'all') . "\n";
        $output .= "Total Records: " . count($logs) . "\n";
        $output .= "===============================================================================\n\n";

        foreach ($logs as $log) {
            $output .= sprintf(
                "[%s] %s: %s\n",
                $log['timestamp'] ?? 'N/A',
                strtoupper($log['level'] ?? 'UNKNOWN'),
                $log['message'] ?? ''
            );

            if (!empty($log['request_id'])) {
                $output .= "  Request-ID: {$log['request_id']}";
                if (!empty($log['correlation_id'])) {
                    $output .= " | Correlation-ID: {$log['correlation_id']}";
                }
                $output .= "\n";
            }

            if (!empty($log['url'])) {
                $output .= "  Route: " . ($log['method'] ?? 'GET') . " {$log['url']}\n";
            }

            if (!empty($log['user_name'])) {
                $output .= "  User: {$log['user_name']} (ID: {$log['user_id']}) | IP: " . ($log['ip'] ?? 'N/A') . "\n";
            }

            if (!empty($log['exception_class'])) {
                $output .= "  Exception: {$log['exception_class']} in {$log['file']}:{$log['line']}\n";
            }

            if (!empty($log['trace'])) {
                $output .= "  Stack Trace:\n" . preg_replace('/^/m', '    ', $log['trace']) . "\n";
            }

            if (!empty($log['context'])) {
                $output .= "  Context: " . json_encode($log['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            }

            $output .= "-------------------------------------------------------------------------------\n";
        }

        return $output;
    }

    /**
     * Safely clear log files and record an immutable audit trail in the database.
     */
    public function clearLogs(User $actor, string $scope = 'all'): array
    {
        $logFiles = File::glob(storage_path('logs/*.log')) ?: [];
        $filesAffected = 0;
        $bytesCleared = 0;

        foreach ($logFiles as $filePath) {
            if (!File::exists($filePath)) {
                continue;
            }

            $size = @filesize($filePath) ?: 0;
            $bytesCleared += $size;
            $filesAffected++;

            // Truncate file cleanly to preserve active file descriptor handles
            @file_put_contents($filePath, '');
        }

        // Record independent DB audit log
        try {
            DB::table('audit_logs')->insert([
                'id' => (string) Str::ulid(),
                'user_id' => $actor->id,
                'event' => 'cleared',
                'auditable_type' => 'SystemLog',
                'auditable_id' => (string) Str::ulid(),
                'old_values' => json_encode([
                    'scope' => $scope,
                    'files_affected' => $filesAffected,
                    'bytes_cleared' => $bytesCleared,
                    'bytes_formatted' => $this->formatBytes($bytesCleared),
                    'request_id' => $this->context->getRequestId(),
                ]),
                'new_values' => json_encode(['status' => 'cleared']),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // DB audit insert fallback
        }

        return [
            'files_affected' => $filesAffected,
            'bytes_cleared' => $bytesCleared,
            'bytes_formatted' => $this->formatBytes($bytesCleared),
        ];
    }

    /**
     * Parse a single log line (JSON Lines primary with legacy text fallback).
     */
    protected function parseLogLine(string $line, string $filePath, int $lineIndex): ?array
    {
        $line = trim($line);
        if (empty($line)) {
            return null;
        }

        // 1. JSON Lines (First-class format)
        if ($line[0] === '{' && ($decoded = json_decode($line, true)) && is_array($decoded)) {
            $extra = $decoded['extra'] ?? [];
            $context = $decoded['context'] ?? [];

            return [
                'id' => md5($filePath . ':' . $lineIndex . ':' . ($decoded['timestamp'] ?? '')),
                'timestamp' => $decoded['timestamp'] ?? Carbon::now()->toIso8601String(),
                'level' => strtoupper($decoded['level'] ?? 'INFO'),
                'message' => $decoded['message'] ?? '',
                'channel' => $decoded['channel'] ?? 'local',
                'exception_class' => $decoded['exception_class'] ?? null,
                'file' => $decoded['file'] ?? null,
                'line' => $decoded['line'] ?? null,
                'trace' => $decoded['trace'] ?? null,
                'has_trace' => !empty($decoded['trace']),
                'request_id' => $extra['request_id'] ?? null,
                'correlation_id' => $extra['correlation_id'] ?? null,
                'job_id' => $extra['job_id'] ?? null,
                'job_class' => $extra['job_class'] ?? null,
                'command' => $extra['command'] ?? null,
                'user_id' => $extra['user_id'] ?? null,
                'user_name' => $extra['user_name'] ?? null,
                'ip' => $extra['ip'] ?? null,
                'method' => $extra['method'] ?? null,
                'url' => $extra['url'] ?? null,
                'environment' => $extra['environment'] ?? 'production',
                'context' => SystemLogSanitizer::sanitize($context),
            ];
        }

        // 2. Legacy Monolog Text Fallback (Compatibility only)
        if (preg_match('/^\[(?P<timestamp>\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2}|Z)?)\]\s+(?:(?P<env>\w+)\.)?(?P<level>[A-Z]+):\s+(?P<message>.*)$/s', $line, $matches)) {
            return [
                'id' => md5($filePath . ':' . $lineIndex . ':' . $matches['timestamp']),
                'timestamp' => $matches['timestamp'],
                'level' => strtoupper($matches['level']),
                'message' => trim($matches['message']),
                'channel' => $matches['env'] ?? 'local',
                'exception_class' => null,
                'file' => null,
                'line' => null,
                'trace' => null,
                'has_trace' => false,
                'request_id' => null,
                'correlation_id' => null,
                'job_id' => null,
                'job_class' => null,
                'command' => null,
                'user_id' => null,
                'user_name' => null,
                'ip' => null,
                'method' => null,
                'url' => null,
                'environment' => $matches['env'] ?? 'local',
                'context' => [],
            ];
        }

        return null;
    }

    protected function getPeriodCutoff(string $period): ?Carbon
    {
        return match ($period) {
            '24h' => Carbon::now()->subHours(24),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            default => null,
        };
    }

    protected function getRelevantLogFiles(?Carbon $cutoff): array
    {
        $files = File::glob(storage_path('logs/*.log')) ?: [];

        if (!$cutoff) {
            return $files;
        }

        $filtered = [];
        foreach ($files as $file) {
            // Check file mtime or laravel-YYYY-MM-DD pattern
            $mtime = @filemtime($file);
            if ($mtime && Carbon::createFromTimestamp($mtime)->gte($cutoff->copy()->subHours(2))) {
                $filtered[] = $file;
            } elseif (basename($file) === 'laravel.log') {
                $filtered[] = $file;
            }
        }

        return $filtered;
    }

    protected function matchesLevel(string $entryLevel, string $levelFilter): bool
    {
        if ($levelFilter === 'all') {
            return true;
        }

        $entryLevel = strtoupper($entryLevel);

        if ($levelFilter === 'error') {
            return in_array($entryLevel, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);
        }

        if ($levelFilter === 'critical') {
            return in_array($entryLevel, ['CRITICAL', 'ALERT', 'EMERGENCY']);
        }

        if ($levelFilter === 'warning') {
            return $entryLevel === 'WARNING';
        }

        if ($levelFilter === 'info') {
            return in_array($entryLevel, ['INFO', 'NOTICE']);
        }

        if ($levelFilter === 'debug') {
            return $entryLevel === 'DEBUG';
        }

        return strtolower($entryLevel) === $levelFilter;
    }

    protected function matchesSearch(array $entry, string $search): bool
    {
        $searchFields = [
            $entry['message'] ?? '',
            $entry['exception_class'] ?? '',
            $entry['file'] ?? '',
            $entry['request_id'] ?? '',
            $entry['correlation_id'] ?? '',
            $entry['url'] ?? '',
            $entry['user_name'] ?? '',
            $entry['trace'] ?? '',
        ];

        $combined = strtolower(implode(' ', $searchFields));
        return str_contains($combined, $search);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
