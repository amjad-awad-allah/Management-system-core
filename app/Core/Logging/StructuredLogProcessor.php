<?php

namespace App\Core\Logging;

use App\Core\Services\CorrelationContext;
use App\Core\Services\SystemLogSanitizer;
use Illuminate\Support\Facades\Auth;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class StructuredLogProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        /** @var CorrelationContext $context */
        $context = app(CorrelationContext::class);

        $extra = $record->extra;
        $contextData = $context->toArray();

        $userId = Auth::id();
        $userName = Auth::user()?->name;

        $extra['request_id'] = $contextData['request_id'] ?? null;
        $extra['correlation_id'] = $contextData['correlation_id'] ?? null;
        $extra['job_id'] = $contextData['job_id'] ?? null;
        $extra['job_class'] = $contextData['job_class'] ?? null;
        $extra['command'] = $contextData['command'] ?? null;
        $extra['user_id'] = $userId;
        $extra['user_name'] = $userName;
        $extra['ip'] = $contextData['ip'] ?? request()?->ip();
        $extra['method'] = $contextData['method'] ?? request()?->method();
        $extra['url'] = $contextData['url'] ?? request()?->fullUrl();
        $extra['environment'] = app()->environment();

        // Sanitize context parameters
        $sanitizedContext = SystemLogSanitizer::sanitize($record->context);

        return $record->with(
            context: $sanitizedContext,
            extra: array_filter($extra, fn($val) => !is_null($val))
        );
    }
}
