<?php

namespace App\Core\Notification\Services;

use App\Core\Models\User;
use App\Core\Models\Notification;
use App\Core\Models\NotificationPreference;
use App\Core\Notification\Data\NotificationMessage;
use App\Core\Notification\Data\NotificationOptions;
use App\Core\Notification\Drivers\InAppDriver;
use App\Core\Notification\Drivers\ChatDriver;
use App\Core\Notification\Drivers\WhatsAppDriver;
use Illuminate\Support\Str;

class NotificationService
{
    private InAppDriver $inAppDriver;
    private ChatDriver $chatDriver;
    private WhatsAppDriver $whatsAppDriver;

    public function __construct(
        InAppDriver $inAppDriver,
        ChatDriver $chatDriver,
        WhatsAppDriver $whatsAppDriver
    ) {
        $this->inAppDriver = $inAppDriver;
        $this->chatDriver = $chatDriver;
        $this->whatsAppDriver = $whatsAppDriver;
    }

    public function send(User $recipient, NotificationMessage $message, NotificationOptions $options): void
    {
        $groupId = (string) Str::ulid();

        // 1. Determine active channels based on preferences
        $channels = $options->channels;

        // Fetch user preferences for this notification type
        $preferences = NotificationPreference::where('user_id', $recipient->id)
            ->where('notification_type', $message->type)
            ->get()
            ->keyBy('channel');

        $activeChannels = [];
        foreach ($channels as $channel) {
            // Quiet Hours check
            if ($channel === 'whatsapp' && isset($preferences['whatsapp'])) {
                $pref = $preferences['whatsapp'];
                if ($pref->quiet_hours_start && $pref->quiet_hours_end) {
                    $nowTime = now()->format('H:i');
                    if ($this->isInQuietHours($nowTime, $pref->quiet_hours_start, $pref->quiet_hours_end)) {
                        continue; // Skip WhatsApp due to quiet hours
                    }
                }
            }

            // If there's an explicit preference, respect it. Else default to enabled.
            if (isset($preferences[$channel])) {
                if ($preferences[$channel]->enabled) {
                    $activeChannels[] = $channel;
                }
            } else {
                $activeChannels[] = $channel;
            }
        }

        // If no channels enabled, default to 'in_app' and 'chat'
        if (empty($activeChannels)) {
            $activeChannels = ['in_app', 'chat'];
        }

        // 2. Deliver via each active channel
        foreach ($activeChannels as $channel) {
            // Create database delivery log
            $notification = Notification::create([
                'id' => (string) Str::ulid(),
                'notification_group_id' => $groupId,
                'user_id' => $recipient->id,
                'delivery_channel' => $channel,
                'type' => $message->type,
                'source_type' => $message->sourceType,
                'source_id' => $message->sourceId,
                'title' => $message->title,
                'message' => $message->message,
                'metadata' => $message->metadata,
                'priority' => $options->priority,
                'period_key' => $message->periodKey,
                'delivery_status' => 'pending',
                'retry_count' => 0,
            ]);

            // Dispatch to corresponding driver
            try {
                $driver = $this->getDriverForChannel($channel);
                if ($driver) {
                    $result = $driver->send($notification);
                    $notification->update([
                        'delivery_status' => $result->status,
                        'external_id' => $result->externalId,
                        'last_attempt_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                $notification->update([
                    'delivery_status' => 'failed',
                    'last_attempt_at' => now(),
                ]);
            }
        }
    }

    public function hasBeenSent(string $sourceType, string $sourceId, string $type, ?string $periodKey = null): bool
    {
        $query = Notification::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('type', $type)
            ->where('delivery_status', 'sent');

        if ($periodKey !== null) {
            $query->where('period_key', $periodKey);
        }

        return $query->exists();
    }

    private function getDriverForChannel(string $channel)
    {
        return match ($channel) {
            'in_app' => $this->inAppDriver,
            'chat' => $this->chatDriver,
            'whatsapp' => $this->whatsAppDriver,
            default => null,
        };
    }

    private function isInQuietHours(string $nowTime, string $start, string $end): bool
    {
        if ($start <= $end) {
            return $nowTime >= $start && $nowTime <= $end;
        }
        return $nowTime >= $start || $nowTime <= $end;
    }
}
