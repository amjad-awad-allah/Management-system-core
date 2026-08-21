<?php

namespace App\Core\Http\Middleware;

use App\Core\Services\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestIdMiddleware
{
    public function __construct(
        protected CorrelationContext $context
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Client-aware Request ID (reuse valid incoming header or generate ULID)
        $incomingRequestId = $request->header('X-Request-Id') ?? $request->header('X-Request-ID');
        $requestId = $this->context->setRequestId($incomingRequestId);

        // 2. Correlation ID (if incoming X-Correlation-ID is set, validate & store)
        $incomingCorrelationId = $request->header('X-Correlation-Id') ?? $request->header('X-Correlation-ID');
        if ($incomingCorrelationId && preg_match('/^[A-Za-z0-9_-]{8,64}$/', $incomingCorrelationId)) {
            $this->context->setCorrelationId($incomingCorrelationId);
        }

        $this->context->setExtra('method', $request->method());
        $this->context->setExtra('url', $request->fullUrl());
        $this->context->setExtra('ip', $request->ip());

        // Process request
        $response = $next($request);

        // Attach X-Request-Id and X-Correlation-Id to response
        $response->headers->set('X-Request-Id', $requestId);
        if ($correlationId = $this->context->getCorrelationId()) {
            $response->headers->set('X-Correlation-Id', $correlationId);
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // Clean context state upon request termination to prevent memory leaks in Octane / long-lived workers
        $this->context->clear();
    }
}
