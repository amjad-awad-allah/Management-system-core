<?php

namespace App\Core\Console\Commands;

use App\Core\Notification\Drivers\ChatDriver;
use App\Core\Notification\Drivers\InAppDriver;
use App\Core\Notification\Drivers\WhatsAppDriver;
use App\Core\Models\Notification;
use App\Core\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class OutboxWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:work {--sleep=3 : Number of seconds to sleep when no events are available} {--once : Run once and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending domain events and notification outbox items non-blockingly';

    /**
     * Execute the console command.
     */
    public function handle(
        InAppDriver $inAppDriver,
        ChatDriver $chatDriver,
        WhatsAppDriver $whatsAppDriver
    ): int {
        $this->info('Starting universal outbox worker...');
        
        $sleep = (int) $this->option('sleep');
        $runOnce = (bool) $this->option('once');

        do {
            $processedDomain = $this->processDomainEventsBatch();
            $processedNotifications = $this->processNotificationOutboxBatch($inAppDriver, $chatDriver, $whatsAppDriver);
            $totalProcessed = $processedDomain + $processedNotifications;

            if ($runOnce) {
                break;
            }

            if ($totalProcessed === 0) {
                sleep($sleep);
            }
        } while ($this->shouldKeepRunning());

        return 0;
    }

    protected function shouldKeepRunning(): bool
    {
        return true;
    }

    /**
     * Process Domain Events Batch (Transactional Domain Outbox).
     */
    public function processDomainEventsBatch(): int
    {
        $events = DB::transaction(function () {
            $query = DB::table('outbox_events')
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('created_at', 'asc')
                ->limit(50);

            // Attempt SKIP LOCKED where available
            try {
                $rows = $query->lockForUpdate()->get();
            } catch (Throwable) {
                $rows = $query->get();
            }

            if ($rows->isNotEmpty()) {
                DB::table('outbox_events')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['status' => 'processing']);
            }

            return $rows;
        });

        if ($events->isEmpty()) {
            return 0;
        }

        $processedCount = 0;
        foreach ($events as $eventRow) {
            try {
                $this->dispatchEvent($eventRow);

                DB::table('outbox_events')
                    ->where('id', $eventRow->id)
                    ->update([
                        'status' => 'completed',
                        'processed_at' => now(),
                    ]);
            } catch (Throwable $e) {
                Log::error("Domain outbox event failed for ID {$eventRow->id}", [
                    'exception' => $e->getMessage()
                ]);

                $attempts = $eventRow->attempts + 1;
                $status = $attempts >= 3 ? 'failed' : 'pending';

                DB::table('outbox_events')
                    ->where('id', $eventRow->id)
                    ->update([
                        'status' => $status,
                        'attempts' => $attempts,
                        'available_at' => $status === 'pending' ? now()->addMinutes(5 * $attempts) : $eventRow->available_at,
                    ]);
            }

            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * Process Universal Notification Outbox Batch (Lock Released Before External Delivery).
     */
    public function processNotificationOutboxBatch(
        InAppDriver $inAppDriver,
        ChatDriver $chatDriver,
        WhatsAppDriver $whatsAppDriver
    ): int {
        // Phase 1: Short DB Transaction to claim batch (<10ms)
        $batch = DB::transaction(function () {
            $query = DB::table('notification_outbox')
                ->where('status', 'pending')
                ->where('available_at', '<=', now())
                ->orderBy('available_at', 'asc')
                ->limit(50);

            try {
                $rows = $query->lockForUpdate()->get();
            } catch (Throwable) {
                $rows = $query->get();
            }

            if ($rows->isNotEmpty()) {
                DB::table('notification_outbox')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['status' => 'processing']);
            }

            return $rows;
        });

        if ($batch->isEmpty()) {
            return 0;
        }

        $processedCount = 0;

        // Phase 2: Deliver OUTSIDE DB Lock with Provider Idempotency Key (correlation_id)
        foreach ($batch as $job) {
            try {
                $payload = is_string($job->payload) ? json_decode($job->payload, true) : (array) $job->payload;
                $driver = match ($job->channel) {
                    'in_app' => $inAppDriver,
                    'chat', 'reverb' => $chatDriver,
                    'whatsapp' => $whatsAppDriver,
                    default => null,
                };

                if (!$driver) {
                    throw new \RuntimeException("Unsupported notification channel: {$job->channel}");
                }

                // Create read-model notification record for user inbox when applicable
                $user = User::find($job->recipient_user_id);
                if (!$user) {
                    throw new \RuntimeException("Recipient user {$job->recipient_user_id} not found");
                }

                $data = (isset($payload['data']) && is_array($payload['data'])) ? $payload['data'] : [];

                $readModel = Notification::create([
                    'id' => (string) Str::ulid(),
                    'notification_group_id' => $job->correlation_id,
                    'user_id' => $user->id,
                    'delivery_channel' => $job->channel,
                    'type' => $payload['notification_type'] ?? 'general',
                    'source_type' => $data['source_type'] ?? 'outbox',
                    'source_id' => $data['source_id'] ?? $job->id,
                    'title' => $data['title'] ?? ($payload['template'] ?? 'Notification'),
                    'message' => $data['message'] ?? 'Notification received',
                    'metadata' => $payload,
                    'priority' => 'normal',
                    'delivery_status' => 'sent',
                    'retry_count' => $job->attempts,
                ]);
                $driver->send($readModel);

                // Mark successful delivery
                DB::table('notification_outbox')
                    ->where('id', $job->id)
                    ->update([
                        'status' => 'sent',
                        'processed_at' => now(),
                        'error_log' => null,
                    ]);

            } catch (Throwable $e) {
                Log::error("Notification outbox delivery failed for ID {$job->id}", [
                    'channel' => $job->channel,
                    'exception' => $e->getMessage(),
                ]);

                $attempts = $job->attempts + 1;
                $maxAttempts = $job->max_attempts ?? 3;
                $status = $attempts >= $maxAttempts ? 'dead' : 'pending';
                $backoffMinutes = (int) pow(2, min($attempts, 6)); // Exponential backoff: 2, 4, 8...

                DB::table('notification_outbox')
                    ->where('id', $job->id)
                    ->update([
                        'status' => $status,
                        'attempts' => $attempts,
                        'available_at' => $status === 'pending' ? now()->addMinutes($backoffMinutes) : $job->available_at,
                        'error_log' => $e->getMessage(),
                    ]);
            }

            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * @param object{id: string, event_type: string, payload: string, attempts: int, available_at: string} $eventRow
     */
    private function dispatchEvent(object $eventRow): void
    {
        $eventType = $eventRow->event_type;
        
        if (!class_exists($eventType)) {
            throw new \RuntimeException("Event class {$eventType} does not exist.");
        }
        
        if (!is_subclass_of($eventType, \App\Shared\Contracts\Events\DomainEvent::class)) {
            throw new \RuntimeException("Event class {$eventType} must implement DomainEvent.");
        }

        $payloadString = $eventRow->payload;
        /** @var array<string, mixed> $payload */
        $payload = json_decode($payloadString, true, 512, JSON_THROW_ON_ERROR);

        $eventInstance = $eventType::fromPayload($payload);
        
        Event::dispatch($eventInstance);
    }
}
