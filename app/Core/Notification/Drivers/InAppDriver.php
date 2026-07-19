<?php

namespace App\Core\Notification\Drivers;

use App\Core\Models\Notification;

class InAppDriver implements NotificationDriver
{
    public function send(Notification $notification): DeliveryResult
    {
        // For in-app, saving to notifications table is the delivery itself.
        return new DeliveryResult('sent', 'inapp_' . $notification->id);
    }
}
