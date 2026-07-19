<?php

namespace App\Core\Notification\Drivers;

class DeliveryResult
{
    public function __construct(
        public string $status, // 'sent' or 'failed'
        public ?string $externalId = null
    ) {}
}
