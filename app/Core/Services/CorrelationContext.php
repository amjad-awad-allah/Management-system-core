<?php

namespace App\Core\Services;

use Illuminate\Support\Str;

class CorrelationContext
{
    protected ?string $requestId = null;
    protected ?string $correlationId = null;
    protected ?string $jobId = null;
    protected ?string $jobClass = null;
    protected ?string $command = null;
    protected array $extra = [];

    /**
     * Set or generate HTTP Request ID (validated alphanumeric/hyphens, 8-64 chars).
     */
    public function setRequestId(?string $incomingId = null): string
    {
        if ($incomingId && preg_match('/^[A-Za-z0-9_-]{8,64}$/', $incomingId)) {
            $this->requestId = $incomingId;
        } else {
            $this->requestId = (string) Str::ulid();
        }

        if (!$this->correlationId) {
            $this->correlationId = $this->requestId;
        }

        return $this->requestId;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setCorrelationId(?string $correlationId = null): string
    {
        $this->correlationId = $correlationId ?: (string) Str::ulid();
        return $this->correlationId;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId ?? $this->requestId;
    }

    public function setJobContext(string $jobId, ?string $jobClass = null): void
    {
        $this->jobId = $jobId;
        $this->jobClass = $jobClass;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function getJobClass(): ?string
    {
        return $this->jobClass;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setExtra(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * Get all structured correlation context data.
     */
    public function toArray(): array
    {
        return array_filter([
            'request_id' => $this->requestId,
            'correlation_id' => $this->getCorrelationId(),
            'job_id' => $this->jobId,
            'job_class' => $this->jobClass,
            'command' => $this->command,
            ...$this->extra,
        ], fn($val) => !is_null($val));
    }

    /**
     * Clear the context (e.g. between requests or queue worker jobs).
     */
    public function clear(): void
    {
        $this->requestId = null;
        $this->correlationId = null;
        $this->jobId = null;
        $this->jobClass = null;
        $this->command = null;
        $this->extra = [];
    }
}
