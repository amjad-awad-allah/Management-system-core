<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Contracts\WhatsAppNotificationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppNotificationService implements WhatsAppNotificationServiceInterface
{
    public function send(string $phoneNumber, string $message): bool
    {
        $dbSid = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'twilio_sid')->value('value');
        $dbToken = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'twilio_token')->value('value');
        $dbFrom = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'twilio_from')->value('value');

        $sid = $dbSid ?: config('services.twilio.sid');
        $token = $dbToken ?: config('services.twilio.token');
        $from = $dbFrom ?: config('services.twilio.from');

        // Normalize destination phone number to Twilio WhatsApp format: whatsapp:+49...
        $to = $phoneNumber;
        if (!str_starts_with($to, 'whatsapp:')) {
            // Strip non-numeric except leading plus
            $cleanNumber = preg_replace('/[^\d+]/', '', $to);
            if (!str_starts_with($cleanNumber, '+')) {
                // Default to German country code if no plus sign is present
                $cleanNumber = '+' . ltrim($cleanNumber, '0');
            }
            $to = 'whatsapp:' . $cleanNumber;
        }

        // Graceful fallback if credentials are not configured
        if (!$sid || !$token || !$from) {
            Log::info("[Twilio WhatsApp SIMULATION] To {$to} (Message): {$message}");
            return true;
        }

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
            
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($url, [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp message successfully sent via Twilio to {$to}");
                return true;
            }

            Log::error("Twilio WhatsApp sending failed: Status: " . $response->status() . " Body: " . $response->body());
            return false;

        } catch (\Throwable $e) {
            Log::error("Twilio WhatsApp Exception: " . $e->getMessage());
            return false;
        }
    }
}
