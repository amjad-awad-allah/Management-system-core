<?php

namespace App\Core\Notification\Data;

class NotificationOptions
{
    public function __construct(
        public array $channels = ['in_app', 'chat', 'whatsapp'],
        public string $priority = 'normal'
    ) {}
}
