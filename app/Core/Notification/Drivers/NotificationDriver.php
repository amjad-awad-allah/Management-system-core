<?php

namespace App\Core\Notification\Drivers;

use App\Core\Models\Notification;

interface NotificationDriver
{
    /**
     * Send a notification and return the delivery result.
     */
    public function send(Notification $notification): DeliveryResult;
}
