<?php

namespace App\Shared\Contracts\Events;

interface DomainEventBus
{
    /**
     * Publish a domain event to the outbox or event dispatchers.
     */
    public function publish(DomainEvent $event): void;
}
