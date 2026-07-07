<?php

namespace App\Shared\Contracts\Events;

interface DomainEvent
{
    /**
     * Get the unique event identifier.
     */
    public function getEventId(): string;

    /**
     * Get the event schema/payload version.
     */
    public function getEventVersion(): int;

    /**
     * Get the date and time when the event occurred.
     */
    public function getOccurredAt(): \DateTimeImmutable;

    /**
     * Get the unique aggregate identifier.
     */
    public function getAggregateId(): string;

    /**
     * Get the correlation identifier for tracking flow.
     */
    public function getCorrelationId(): ?string;

    /**
     * Get the serializable event payload.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * Reconstruct the event from its serialized payload.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self;
}
