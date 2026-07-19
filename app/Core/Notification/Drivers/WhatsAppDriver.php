<?php

namespace App\Core\Notification\Drivers;

use App\Core\Models\Notification;
use App\Core\Notification\Contracts\WhatsAppNotificationServiceInterface;
use Illuminate\Support\Facades\DB;

class WhatsAppDriver implements NotificationDriver
{
    private WhatsAppNotificationServiceInterface $whatsappService;

    public function __construct(WhatsAppNotificationServiceInterface $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function send(Notification $notification): DeliveryResult
    {
        // Try resolving the phone number from metadata first
        $metadata = $notification->metadata;
        $phone = $metadata['parent_phone'] ?? null;

        if (!$phone) {
            // Resolve from Student table
            $student = DB::table('students')->where('user_id', $notification->user_id)->first();
            $phone = $student->parent_phone_1 ?? null;
        }

        if (!$phone) {
            // No phone number found, delivery fails
            return new DeliveryResult('failed');
        }

        // Send via core WhatsApp service
        $success = $this->whatsappService->send($phone, $notification->message);

        return new DeliveryResult($success ? 'sent' : 'failed', 'wa_mid_' . uniqid());
    }
}
