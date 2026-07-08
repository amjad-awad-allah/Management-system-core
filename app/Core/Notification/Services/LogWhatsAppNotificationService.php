<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Contracts\WhatsAppNotificationServiceInterface;
use Illuminate\Support\Facades\Log;

class LogWhatsAppNotificationService implements WhatsAppNotificationServiceInterface
{
    public function send(string $phoneNumber, string $message): bool
    {
        // In a real application, this would call a WhatsApp API provider (e.g. Twilio, Meta API)
        // For now, we simulate the sending by logging it.
        Log::info("WhatsApp Message to {$phoneNumber}: {$message}");
        
        return true;
    }
}
