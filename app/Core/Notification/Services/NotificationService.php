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
use App\Core\Notification\Models\NotificationEvent;
use App\Core\Notification\Models\NotificationOutbox;
use Illuminate\Support\Facades\DB;
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

    /**
     * Universal Outbox Enqueueing with Business Idempotency.
     *
     * @param string $eventType e.g. 'lesson', 'invoice', 'payment'
     * @param string $eventId Target entity ULID
     * @param string $notificationType e.g. 'reminder_24h', 'reminder_2h', 'cancelled'
     * @param string $recipientUserId
     * @param array<string> $channels e.g. ['in_app', 'reverb', 'whatsapp', 'email']
     * @param array<string, mixed> $payload Snapshot containing schema_version, template, template_version, locale, data
     * @param string $tenantId
     * @param \DateTimeInterface|null $scheduledAt
     * @return NotificationEvent
     */
    public function enqueue(
        string $eventType,
        string $eventId,
        string $notificationType,
        string $recipientUserId,
        array $channels,
        array $payload,
        string $tenantId = 'default',
        ?\DateTimeInterface $scheduledAt = null
    ): NotificationEvent {
        return DB::transaction(function () use (
            $eventType,
            $eventId,
            $notificationType,
            $recipientUserId,
            $channels,
            $payload,
            $tenantId,
            $scheduledAt
        ) {
            // 1. Business Idempotency: Find or create notification event
            /** @var NotificationEvent $event */
            $event = NotificationEvent::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'notification_type' => $notificationType,
                    'recipient_user_id' => $recipientUserId,
                ],
                [
                    'id' => (string) Str::ulid(),
                    'status' => 'generated',
                    'scheduled_at' => $scheduledAt,
                    'triggered_at' => now(),
                    'metadata' => $payload['data'] ?? null,
                ]
            );

            // 2. Create outbox records for each channel (ignoring duplicates via unique constraint / firstOrCreate)
            $correlationId = (string) Str::ulid();
            $availableAt = $scheduledAt ? \Carbon\Carbon::instance($scheduledAt) : now();

            foreach ($channels as $channel) {
                NotificationOutbox::firstOrCreate(
                    [
                        'notification_event_id' => $event->id,
                        'channel' => $channel,
                    ],
                    [
                        'id' => (string) Str::ulid(),
                        'tenant_id' => $tenantId,
                        'correlation_id' => $correlationId,
                        'recipient_user_id' => $recipientUserId,
                        'payload' => $payload,
                        'status' => 'pending',
                        'attempts' => 0,
                        'max_attempts' => 3,
                        'available_at' => $availableAt,
                    ]
                );
            }

            return $event;
        });
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
