<?php

namespace App\Core\Notification\Data;

class NotificationMessage
{
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?string $periodKey = null,
        public ?array $metadata = null
    ) {}
}
