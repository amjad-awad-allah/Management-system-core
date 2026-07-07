<?php

namespace App\Core\Events;

use App\Shared\Contracts\Events\DomainEvent;
use App\Shared\Contracts\Events\DomainEventBus;
use Illuminate\Support\Facades\DB;

class OutboxEventBus implements DomainEventBus
{
    public function publish(DomainEvent $event): void
    {
        DB::table('outbox_events')->insert([
            'id' => $event->getEventId(),
            'event_type' => get_class($event),
            'event_version' => $event->getEventVersion(),
            'payload' => json_encode($event->toPayload(), JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
