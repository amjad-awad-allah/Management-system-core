<?php

namespace App\Core\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Throwable;

class JsonLinesFormatter extends NormalizerFormatter
{
    public function __construct(?string $dateFormat = 'Y-m-d\TH:i:sP')
    {
        parent::__construct($dateFormat);
    }

    public function format(LogRecord $record): string
    {
        $normalized = $this->normalizeRecord($record);

        $entry = [
            'timestamp' => $record->datetime->format($this->dateFormat),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'channel' => $record->channel,
        ];

        // Extract exception details if present
        if (isset($record->context['exception']) && $record->context['exception'] instanceof Throwable) {
            $e = $record->context['exception'];
            $entry['exception_class'] = get_class($e);
            $entry['file'] = $e->getFile();
            $entry['line'] = $e->getLine();
            $entry['trace'] = $e->getTraceAsString();
        } elseif (isset($normalized['context']['exception'])) {
            $exc = $normalized['context']['exception'];
            if (is_array($exc)) {
                $entry['exception_class'] = $exc['class'] ?? null;
                $entry['file'] = $exc['file'] ?? null;
                $entry['line'] = $exc['line'] ?? null;
                $entry['trace'] = $exc['trace'] ?? null;
            }
        }

        // Merge extra metadata (request_id, correlation_id, user_id, ip, url, etc.)
        if (!empty($normalized['extra'])) {
            $entry['extra'] = $normalized['extra'];
        }

        // Clean context (without raw exception duplicate if already extracted)
        $cleanContext = $normalized['context'] ?? [];
        unset($cleanContext['exception']);
        if (!empty($cleanContext)) {
            $entry['context'] = $cleanContext;
        }

        return $this->toJson($entry, true) . "\n";
    }
}
